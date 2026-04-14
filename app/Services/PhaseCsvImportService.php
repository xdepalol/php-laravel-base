<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Phase;
use Carbon\CarbonImmutable;
use League\Csv\Reader;

/**
 * Importa fases / sprints desde texto CSV o pegado desde Excel (misma idea que {@see BacklogCsvImportService}).
 *
 * @return array{created: int, skipped: int, errors: list<array{row: int, message: string}>}
 */
class PhaseCsvImportService
{
    /** @var list<string> */
    private const HEADER_MARKERS = [
        'title', 'titulo', 'título', 'nombre', 'sprint', 'is_sprint', 'inicio', 'fin', 'start', 'end',
        'fecha', 'desde', 'hasta', 'fase',
    ];

    public function import(Activity $activity, string $rawCsv): array
    {
        $created = 0;
        $skipped = 0;
        $errors = [];

        $rawCsv = $this->stripBom(trim($rawCsv));
        if ($rawCsv === '') {
            return $this->result($created, $skipped, $errors);
        }

        $firstLine = $this->firstNonEmptyLine($rawCsv);
        if ($firstLine === null) {
            return $this->result($created, $skipped, $errors);
        }

        $reader = Reader::createFromString($rawCsv);
        $reader->setDelimiter($this->sniffDelimiter($firstLine));

        $rows = [];
        foreach ($reader->getRecords() as $record) {
            $rows[] = array_values(array_map(fn ($c) => is_string($c) ? trim($c) : (string) $c, $record));
        }

        if ($rows === []) {
            return $this->result($created, $skipped, $errors);
        }

        $firstRow = $rows[0];
        $hasHeader = $this->rowLooksLikeHeader($firstRow);
        if ($hasHeader) {
            $columnMap = $this->buildColumnMapFromHeader($firstRow);
            $dataRows = array_slice($rows, 1);
            $lineStart = 2;
        } else {
            $columnMap = $this->defaultColumnMap(count($firstRow));
            $dataRows = $rows;
            $lineStart = 1;
        }

        foreach ($dataRows as $i => $row) {
            $lineNum = $lineStart + $i;
            $assoc = $this->rowToAssoc($row, $columnMap);
            if ($this->isAssocEmpty($assoc)) {
                $skipped++;

                continue;
            }
            $title = isset($assoc['title']) ? mb_substr(trim((string) $assoc['title']), 0, 255) : '';
            if ($title === '') {
                $errors[] = ['row' => $lineNum, 'message' => 'Falta el título.'];
                $skipped++;

                continue;
            }

            $isSprint = $this->parseBool($assoc['is_sprint'] ?? null);
            $start = $this->parseDate($assoc['start_date'] ?? null);
            $end = $this->parseDate($assoc['end_date'] ?? null);

            if ($start !== null && $end !== null && $end->lt($start)) {
                $errors[] = ['row' => $lineNum, 'message' => 'La fecha de fin es anterior a la de inicio.'];
                $skipped++;

                continue;
            }

            Phase::query()->create([
                'activity_id' => $activity->id,
                'title' => $title,
                'is_sprint' => $isSprint,
                'start_date' => $start,
                'end_date' => $end,
                'retro_well' => null,
                'retro_bad' => null,
                'retro_improvement' => null,
                'teacher_feedback' => null,
            ]);
            $created++;
        }

        return $this->result($created, $skipped, $errors);
    }

    /**
     * @param  list<array{row: int, message: string}>  $errors
     * @return array{created: int, skipped: int, errors: list<array{row: int, message: string}>}
     */
    private function result(int $created, int $skipped, array $errors): array
    {
        return [
            'created' => $created,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }

    private function stripBom(string $s): string
    {
        if (str_starts_with($s, "\xEF\xBB\xBF")) {
            return substr($s, 3);
        }

        return $s;
    }

    private function firstNonEmptyLine(string $csv): ?string
    {
        foreach (preg_split("/\r\n|\n|\r/", $csv) as $line) {
            if (trim($line) !== '') {
                return $line;
            }
        }

        return null;
    }

    private function sniffDelimiter(string $line): string
    {
        $tab = substr_count($line, "\t");
        $comma = substr_count($line, ',');
        $semi = substr_count($line, ';');

        if ($tab >= $comma && $tab >= $semi && $tab > 0) {
            return "\t";
        }
        if ($semi > $comma) {
            return ';';
        }

        return ',';
    }

    /**
     * @param  list<string>  $cells
     */
    private function rowLooksLikeHeader(array $cells): bool
    {
        if ($cells === []) {
            return false;
        }
        $joined = strtolower(implode('|', $cells));
        foreach (self::HEADER_MARKERS as $marker) {
            if (str_contains($joined, $marker)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<string>  $headerCells
     * @return array<int, string>
     */
    private function buildColumnMapFromHeader(array $headerCells): array
    {
        $map = [];
        foreach ($headerCells as $i => $cell) {
            $field = $this->mapHeaderCell($cell);
            $map[$i] = $field ?? 'ignore';
        }

        return $map;
    }

    private function mapHeaderCell(string $cell): ?string
    {
        $k = strtolower(trim(str_replace(
            ['á', 'é', 'í', 'ó', 'ú', 'ñ', 'ü'],
            ['a', 'e', 'i', 'o', 'u', 'n', 'u'],
            $cell
        )));

        return match (true) {
            in_array($k, ['title', 'titulo', 'nombre', 'fase', 'phase', 'historia'], true) => 'title',
            in_array($k, ['is_sprint', 'sprint', 'es_sprint', 'iteracion'], true) => 'is_sprint',
            in_array($k, ['start_date', 'inicio', 'start', 'desde', 'fecha_inicio'], true) => 'start_date',
            in_array($k, ['end_date', 'fin', 'end', 'hasta', 'fecha_fin'], true) => 'end_date',
            default => null,
        };
    }

    /**
     * @return array<int, string>
     */
    private function defaultColumnMap(int $colCount): array
    {
        $order = ['title', 'is_sprint', 'start_date', 'end_date'];
        $map = [];
        for ($i = 0; $i < $colCount; $i++) {
            $map[$i] = $order[$i] ?? 'ignore';
        }

        return $map;
    }

    /**
     * @param  list<string>  $row
     * @param  array<int, string>  $columnMap
     * @return array{title: ?string, is_sprint: ?string, start_date: ?string, end_date: ?string}
     */
    private function rowToAssoc(array $row, array $columnMap): array
    {
        $out = [
            'title' => null,
            'is_sprint' => null,
            'start_date' => null,
            'end_date' => null,
        ];
        foreach ($columnMap as $colIdx => $field) {
            if ($field === 'ignore') {
                continue;
            }
            if (! isset($row[$colIdx])) {
                continue;
            }
            $val = $row[$colIdx];
            $out[$field] = $val === '' ? null : $val;
        }

        return $out;
    }

    /**
     * @param  array{title: ?string, is_sprint: ?string, start_date: ?string, end_date: ?string}  $assoc
     */
    private function isAssocEmpty(array $assoc): bool
    {
        foreach ($assoc as $v) {
            if ($v !== null && $v !== '') {
                return false;
            }
        }

        return true;
    }

    private function parseBool(?string $raw): bool
    {
        if ($raw === null || $raw === '') {
            return false;
        }
        $s = mb_strtolower(trim($raw));

        return in_array($s, ['1', 'true', 'yes', 'y', 's', 'si', 'sí', 'sprint', 'x', 'verdadero'], true);
    }

    private function parseDate(?string $raw): ?CarbonImmutable
    {
        if ($raw === null || trim($raw) === '') {
            return null;
        }
        $s = trim($raw);
        try {
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $s)) {
                return CarbonImmutable::createFromFormat('Y-m-d', $s)->startOfDay();
            }
            if (preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $s)) {
                return CarbonImmutable::createFromFormat('d/m/Y', $s)->startOfDay();
            }

            return CarbonImmutable::parse($s)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }
}
