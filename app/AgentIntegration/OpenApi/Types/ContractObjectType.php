<?php

declare(strict_types=1);

namespace App\AgentIntegration\OpenApi\Types;

use Dedoc\Scramble\Support\Generator\Types\ObjectType;
use LogicException;

final class ContractObjectType extends ObjectType
{
    private ?int $minimumProperties = null;

    /** @var list<list<string>> */
    private array $requiredAlternatives = [];

    public function requireAtLeastProperties(int $minimum): self
    {
        $this->minimumProperties = $minimum;

        return $this;
    }

    /** @param list<list<string>> $alternatives */
    public function requireAnyOf(array $alternatives): self
    {
        $this->requiredAlternatives = $alternatives;

        return $this;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $schema = parent::toArray();
        if ( ! is_array($schema)) {
            throw new LogicException('Scramble did not serialize the contract object schema as an array.');
        }
        $schema = $this->stringKeyed($schema);

        return [
            ...$schema,
            'additionalProperties' => false,
            ...($this->minimumProperties === null ? [] : ['minProperties' => $this->minimumProperties]),
            ...($this->requiredAlternatives === [] ? [] : [
                'anyOf' => array_map(
                    fn (array $required): array => ['required' => $required],
                    $this->requiredAlternatives,
                ),
            ]),
        ];
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
                throw new LogicException('An OpenAPI object schema must use string field names.');
            }
            $result[$key] = $value;
        }

        return $result;
    }
}
