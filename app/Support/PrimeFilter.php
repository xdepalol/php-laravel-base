<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class PrimeFilter
{
    public const STARTS_WITH = 'startsWith';
    public const CONTAINS = 'contains';
    public const NOT_CONTAINS = 'notContains';
    public const ENDS_WITH = 'endsWith';
    public const EQUALS = 'equals';
    public const NOT_EQUALS = 'notEquals';

    public function __construct(
        public string $field,
        public mixed $value = null,
        public string $matchMode = self::CONTAINS
    ) {}

    public function apply(Builder $query, bool $or = false): void
    {
        // Ignora buits
        if ($this->value === null || $this->value === '') {
            return;
        }

        $method = $or ? 'orWhere' : 'where';

        switch ($this->matchMode) {
            case self::STARTS_WITH:
                $query->{$method}($this->field, 'like', $this->value . '%');
                break;

            case self::ENDS_WITH:
                $query->{$method}($this->field, 'like', '%' . $this->value);
                break;

            case self::EQUALS:
                $query->{$method}($this->field, '=', $this->value);
                break;

            case self::NOT_EQUALS:
                $query->{$method}($this->field, '!=', $this->value);
                break;

            case self::NOT_CONTAINS:
                // Nota: NOT LIKE
                $query->{$method}($this->field, 'not like', '%' . $this->value . '%');
                break;

            case self::CONTAINS:
            default:
                $query->{$method}($this->field, 'like', '%' . $this->value . '%');
                break;
        }
    }

    /**
     * Llegeix els filtres des del Request en qualsevol format suportat i retorna
     * un array associatiu de PrimeFilter indexat pel nom de camp.
     *
     * Sempre inclou (com a key) "global" si està present.
     *
     * @param Request $request
     * @param array|null $allowedFilters  Llista blanca de camps locals permesos (sense "global").
     * @param string $paramName           Nom del paràmetre (per defecte "filter")
     * @return array<string, PrimeFilter> ['global' => PrimeFilter, 'surname1' => PrimeFilter, ...]
     */
    public static function getFiltersFromRequest(Request $request, ?array $allowedFilters = null, string $paramName = 'filter'): array
    {
        $raw = $request->query($paramName, []);

        // Normalitza: si ve com string (rar) el convertim a array buit
        if (!is_array($raw)) {
            $raw = [];
        }

        $result = [];

        foreach ($raw as $field => $payload) {
            // 1) "global" sempre permès, la resta passa per allowedFilters si s'ha informat
            if ($field !== 'global' && is_array($allowedFilters) && !in_array($field, $allowedFilters, true)) {
                continue;
            }

            // 2) Parse tolerant de value + matchMode (accepta múltiples formats)
            [$value, $matchMode] = self::extractValueAndMatchMode($payload);

            // buits fora (però si vols conservar-ho, ho canvies aquí)
            if ($value === null || $value === '') {
                continue;
            }

            $result[$field] = new self($field, $value, $matchMode ?? self::CONTAINS);
        }

        return $result;
    }

    /**
     * Accepta:
     * - string: "sal"
     * - array normalitzat: ['value' => 'sal', 'matchMode' => 'startsWith']
     * - array PrimeVue menu: ['operator' => ..., 'constraints' => [['value'=>..., 'matchMode'=>...], ...]]
     *
     * Retorna [value, matchMode]
     */
    private static function extractValueAndMatchMode(mixed $payload): array
    {
        // String legacy: filter[field]=sal
        if (!is_array($payload)) {
            return [$payload, null];
        }

        // Normalitzat: filter[field][value], filter[field][matchMode]
        $value = $payload['value'] ?? null;
        $matchMode = $payload['matchMode'] ?? null;

        if ($value !== null) {
            return [$value, $matchMode];
        }

        // PrimeVue menu: constraints[0]
        if (isset($payload['constraints']) && is_array($payload['constraints'])) {
            $c0 = $payload['constraints'][0] ?? null;
            if (is_array($c0)) {
                $value = $c0['value'] ?? null;
                $matchMode = $c0['matchMode'] ?? null;
                return [$value, $matchMode];
            }
        }

        // Fallback: no reconegut
        return [null, null];
    }
}
