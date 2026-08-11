<?php

declare(strict_types=1);

namespace App\AgentIntegration\OpenApi\Types;

use Dedoc\Scramble\Support\Generator\Types\Type;
use InvalidArgumentException;
use LogicException;

final class OneOfType extends Type
{
    /** @var list<Type> */
    private array $items;

    /** @param list<Type> $items */
    public function __construct(array $items)
    {
        parent::__construct('oneOf');

        if ($items === []) {
            throw new InvalidArgumentException('A oneOf schema requires at least one item.');
        }

        $this->items = $items;
    }

    public function clone(): static
    {
        $clone = parent::clone();
        $clone->items = array_map(
            fn (Type $item): Type => $item->clone(),
            $clone->items,
        );

        return $clone;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $schema = parent::toArray();
        if ( ! is_array($schema)) {
            throw new LogicException('Scramble did not serialize the base oneOf schema as an array.');
        }
        $schema = $this->stringKeyed($schema);
        unset($schema['type']);

        return [
            ...$schema,
            'oneOf' => array_map(
                fn (Type $item): array => $this->serialize($item),
                $this->items,
            ),
        ];
    }

    /** @return array<string, mixed> */
    private function serialize(Type $type): array
    {
        $schema = $type->toArray();
        if ( ! is_array($schema)) {
            throw new LogicException('Scramble did not serialize a oneOf item as an array.');
        }

        return $this->stringKeyed($schema);
    }

    /**
     * @param  array<mixed, mixed>  $schema
     * @return array<string, mixed>
     */
    private function stringKeyed(array $schema): array
    {
        $result = [];
        foreach ($schema as $key => $value) {
            if ( ! is_string($key)) {
                throw new LogicException('An OpenAPI schema must use string field names.');
            }
            $result[$key] = $value;
        }

        return $result;
    }
}
