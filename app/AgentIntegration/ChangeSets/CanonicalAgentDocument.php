<?php

declare(strict_types=1);

namespace App\AgentIntegration\ChangeSets;

use Brick\Math\BigDecimal;
use Illuminate\Support\Carbon;
use JsonException;

final readonly class CanonicalAgentDocument
{
    /** @var list<string> */
    private const DECIMAL_FIELDS = [
        'base_servings',
        'serving_count',
        'quantity',
        'weight_grams',
        'volume_millilitres',
        'piece_count',
        'basis_quantity',
        'energy_kcal',
        'fat_grams',
        'protein_grams',
        'carbohydrate_grams',
    ];

    /**
     * @param  array<string, mixed>  $document
     * @return array<string, mixed>
     */
    public function canonicalize(array $document): array
    {
        $canonical = $this->value($document);

        if ( ! is_array($canonical)) {
            throw new JsonException('A canonical Change Set must be a JSON object.');
        }

        $canonicalDocument = [];
        foreach ($canonical as $key => $value) {
            if ( ! is_string($key)) {
                throw new JsonException('A canonical Change Set must use string object keys.');
            }

            $canonicalDocument[$key] = $value;
        }

        if (isset($canonicalDocument['operations']) && is_array($canonicalDocument['operations'])) {
            usort($canonicalDocument['operations'], static function (mixed $left, mixed $right): int {
                $leftId = is_array($left) && is_string($left['operation_id'] ?? null)
                    ? $left['operation_id']
                    : '';
                $rightId = is_array($right) && is_string($right['operation_id'] ?? null)
                    ? $right['operation_id']
                    : '';

                return $leftId <=> $rightId;
            });
        }

        return $canonicalDocument;
    }

    /** @param array<string, mixed> $canonicalDocument */
    public function digest(array $canonicalDocument): string
    {
        return hash('sha256', $this->json($canonicalDocument));
    }

    /** @param array<string, mixed> $canonicalDocument */
    public function json(array $canonicalDocument): string
    {
        return json_encode(
            $canonicalDocument,
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION,
        );
    }

    private function scalar(mixed $value, ?string $key): mixed
    {
        if (is_string($value) && $key !== null && in_array($key, self::DECIMAL_FIELDS, true)) {
            return (string) BigDecimal::of($value)->strippedOfTrailingZeros();
        }

        if (is_string($value) && $key === 'expected_updated_at') {
            return Carbon::parse($value)->utc()->format('Y-m-d\TH:i:s\Z');
        }

        return $value;
    }

    private function value(mixed $value, ?string $key = null): mixed
    {
        if ( ! is_array($value)) {
            return $this->scalar($value, $key);
        }

        if (array_is_list($value)) {
            return array_map(fn (mixed $item): mixed => $this->value($item), $value);
        }

        ksort($value, SORT_STRING);

        foreach ($value as $childKey => $childValue) {
            $value[$childKey] = $this->value($childValue, is_string($childKey) ? $childKey : null);
        }

        return $value;
    }
}
