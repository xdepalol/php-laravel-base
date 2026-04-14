<?php

namespace App\Services;

use App\Enums\BacklogItemPriority;
use App\Enums\BacklogItemStatus;
use App\Models\Activity;
use App\Models\BacklogItem;
use App\Models\Team;
use League\Csv\Reader;

/**
 * Parses pasted or uploaded CSV/TSV in memory with {@see Reader::createFromString} (League CSV).
 * Delimiter is sniffed from the first line (tab for Excel paste, comma, or semicolon).
 * Header row is optional; columns map by Spanish/English names or by fixed column order.
 */
class BacklogCsvImportService
{
    /** @var list<string> */
    private const HEADER_MARKERS = [
        'position', 'posición', 'posicion', 'pos', 'orden', 'titulo', 'título', 'title',
        'descripcion', 'descripción', 'description', 'prioridad', 'priority', 'puntos', 'points',
        'estado', 'status',
    ];

    /**
     * @return array{created: int, updated: int, skipped: int, errors: list<array{row: int, message: string}>}
     */
    public function import(Activity $activity, Team $team, string $rawCsv): array
    {
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];

        $rawCsv = $this->stripBom(trim($rawCsv));
        if ($rawCsv === '') {
            return $this->result($created, $updated, $skipped, $errors);
        }

        $firstLine = $this->firstNonEmptyLine($rawCsv);
        if ($firstLine === null) {
            return $this->result($created, $updated, $skipped, $errors);
        }

        $reader = Reader::createFromString($rawCsv);
        $reader->setDelimiter($this->sniffDelimiter($firstLine));

        $rows = [];
        foreach ($reader->getRecords() as $record) {
            $rows[] = array_values(array_map(fn ($c) => is_string($c) ? trim($c) : (string) $c, $record));
        }

        if ($rows === []) {
            return $this->result($created, $updated, $skipped, $errors);
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

        $parsed = [];
        foreach ($dataRows as $i => $row) {
            $lineNum = $lineStart + $i;
            $assoc = $this->rowToAssoc($row, $columnMap);
            if ($this->isAssocEmpty($assoc)) {
                $skipped++;

                continue;
            }
            $norm = $this->normalizeAssoc($assoc);
            if ($norm['title'] === '') {
                $errors[] = ['row' => $lineNum, 'message' => 'Falta el título.'];
                $skipped++;

                continue;
            }
            $parsed[] = ['line' => $lineNum, 'data' => $norm];
        }

        /** @var array<int, BacklogItem> $byPosition */
        $byPosition = [];
        foreach (BacklogItem::query()
            ->where('activity_id', $activity->id)
            ->where('team_id', $team->id)
            ->get() as $item) {
            $byPosition[(int) $item->position] = $item;
        }

        $numbered = [];
        $unnumbered = [];
        foreach ($parsed as $p) {
            if ($p['data']['position'] !== null) {
                $numbered[] = $p;
            } else {
                $unnumbered[] = $p;
            }
        }

        usort($numbered, fn ($a, $b) => $a['data']['position'] <=> $b['data']['position']);

        foreach ($numbered as $p) {
            $d = $p['data'];
            $pos = (int) $d['position'];
            $payload = $this->payloadForModel($activity, $team, $d, $pos);

            if (isset($byPosition[$pos])) {
                $byPosition[$pos]->update($payload);
                $updated++;
            } else {
                $byPosition[$pos] = BacklogItem::query()->create($payload);
                $created++;
            }
        }

        $nextPos = 0;
        foreach (array_keys($byPosition) as $k) {
            $nextPos = max($nextPos, (int) $k);
        }

        foreach ($unnumbered as $p) {
            $nextPos++;
            $d = $p['data'];
            $payload = $this->payloadForModel($activity, $team, $d, $nextPos);
            $byPosition[$nextPos] = BacklogItem::query()->create($payload);
            $created++;
        }

        return $this->result($created, $updated, $skipped, $errors);
    }

    /**
     * @param  list<array{row: int, message: string}>  $errors
     * @return array{created: int, updated: int, skipped: int, errors: list<array{row: int, message: string}>}
     */
    private function result(int $created, int $updated, int $skipped, array $errors): array
    {
        return [
            'created' => $created,
            'updated' => $updated,
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
            in_array($k, ['position', 'pos', 'posicion', 'orden', '#', 'n', 'no', 'num'], true) => 'position',
            in_array($k, ['title', 'titulo', 'nombre', 'historia', 'story', 'item'], true) => 'title',
            in_array($k, ['description', 'descripcion', 'desc', 'detalle', 'notas'], true) => 'description',
            in_array($k, ['priority', 'prioridad', 'pri'], true) => 'priority',
            in_array($k, ['points', 'puntos', 'pts', 'story points', 'sp'], true) => 'points',
            in_array($k, ['status', 'estado'], true) => 'status',
            default => null,
        };
    }

    /**
     * @return array<int, string>
     */
    private function defaultColumnMap(int $colCount): array
    {
        $order = ['position', 'title', 'description', 'priority', 'points', 'status'];
        $map = [];
        for ($i = 0; $i < $colCount; $i++) {
            $map[$i] = $order[$i] ?? 'ignore';
        }

        return $map;
    }

    /**
     * @param  list<string>  $row
     * @param  array<int, string>  $columnMap
     * @return array{position: ?string, title: ?string, description: ?string, priority: ?string, points: ?string, status: ?string}
     */
    private function rowToAssoc(array $row, array $columnMap): array
    {
        $out = [
            'position' => null,
            'title' => null,
            'description' => null,
            'priority' => null,
            'points' => null,
            'status' => null,
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
     * @param  array{position: ?string, title: ?string, description: ?string, priority: ?string, points: ?string, status: ?string}  $assoc
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

    /**
     * @param  array{position: ?string, title: ?string, description: ?string, priority: ?string, points: ?string, status: ?string}  $assoc
     * @return array{position: ?int, title: string, description: ?string, priority: BacklogItemPriority, points: ?int, status: BacklogItemStatus}
     */
    private function normalizeAssoc(array $assoc): array
    {
        $position = null;
        if (isset($assoc['position']) && $assoc['position'] !== null && $assoc['position'] !== '') {
            $position = (int) $assoc['position'];
        }

        $title = isset($assoc['title']) ? mb_substr(trim($assoc['title']), 0, 150) : '';

        $description = null;
        if (isset($assoc['description']) && $assoc['description'] !== null && trim($assoc['description']) !== '') {
            $description = trim($assoc['description']);
        }

        return [
            'position' => $position,
            'title' => $title,
            'description' => $description,
            'priority' => $this->parsePriority($assoc['priority'] ?? null),
            'points' => $this->parsePoints($assoc['points'] ?? null),
            'status' => $this->parseStatus($assoc['status'] ?? null),
        ];
    }

    private function parsePriority(?string $v): BacklogItemPriority
    {
        if ($v === null || $v === '') {
            return BacklogItemPriority::MEDIUM;
        }
        $t = trim($v);
        if (is_numeric($t)) {
            return BacklogItemPriority::tryFrom((int) $t) ?? BacklogItemPriority::MEDIUM;
        }
        $t = strtoupper(str_replace(' ', '_', $t));
        foreach (BacklogItemPriority::cases() as $case) {
            if ($case->name === $t) {
                return $case;
            }
        }

        return BacklogItemPriority::MEDIUM;
    }

    private function parsePoints(?string $v): ?int
    {
        if ($v === null || $v === '') {
            return null;
        }
        if (! is_numeric(trim($v))) {
            return null;
        }

        return max(0, (int) $v);
    }

    private function parseStatus(?string $v): BacklogItemStatus
    {
        if ($v === null || $v === '') {
            return BacklogItemStatus::BACKLOG;
        }
        $t = trim($v);
        if (is_numeric($t)) {
            return BacklogItemStatus::tryFrom((int) $t) ?? BacklogItemStatus::BACKLOG;
        }
        $k = strtolower($t);

        return match (true) {
            in_array($k, ['backlog', 'borrador', 'pendiente', 'todo', 'por hacer'], true) => BacklogItemStatus::BACKLOG,
            in_array($k, ['in_progress', 'en curso', 'progreso', 'doing', 'en_progreso'], true) => BacklogItemStatus::IN_PROGRESS,
            in_array($k, ['done', 'hecho', 'completado', 'completo'], true) => BacklogItemStatus::DONE,
            in_array($k, ['cancelled', 'cancelado', 'descartado'], true) => BacklogItemStatus::CANCELLED,
            default => BacklogItemStatus::BACKLOG,
        };
    }

    /**
     * @param  array{position: ?int, title: string, description: ?string, priority: BacklogItemPriority, points: ?int, status: BacklogItemStatus}  $d
     * @return array{activity_id: int, team_id: int, title: string, description: ?string, priority: BacklogItemPriority, points: ?int, status: BacklogItemStatus, position: int}
     */
    private function payloadForModel(Activity $activity, Team $team, array $d, int $position): array
    {
        return [
            'activity_id' => $activity->id,
            'team_id' => $team->id,
            'title' => $d['title'],
            'description' => $d['description'],
            'priority' => $d['priority'],
            'points' => $d['points'],
            'status' => $d['status'],
            'position' => $position,
        ];
    }
}
