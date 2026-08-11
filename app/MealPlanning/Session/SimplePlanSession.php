<?php

declare(strict_types=1);

namespace App\MealPlanning\Session;

use App\MealPlanning\Values\SimplePlan;
use Illuminate\Contracts\Session\Session;

final class SimplePlanSession
{
    public function load(Session $session, int $familyId): SimplePlan
    {
        $value = $session->get($this->key($familyId), []);

        return is_array($value) ? SimplePlan::fromArray($value) : SimplePlan::empty();
    }

    public function save(Session $session, int $familyId, SimplePlan $simplePlan): void
    {
        $session->put($this->key($familyId), $simplePlan->toArray());
        $session->forget([$this->generatedKey($familyId), $this->alternativesKey($familyId)]);
    }

    public function forget(Session $session, int $familyId): void
    {
        $session->forget([
            $this->key($familyId),
            $this->generatedKey($familyId),
            $this->alternativesKey($familyId),
        ]);
    }

    /** @param array<string, mixed> $presentation */
    public function saveGenerated(Session $session, int $familyId, array $presentation): void
    {
        $session->put($this->generatedKey($familyId), $presentation);
    }

    /** @return array<int, int> */
    public function alternatives(Session $session, int $familyId): array
    {
        $value = $session->get($this->alternativesKey($familyId), []);
        if ( ! is_array($value)) {
            return [];
        }

        $alternatives = [];
        foreach ($value as $originalId => $alternativeId) {
            if (is_numeric($originalId) && is_numeric($alternativeId)) {
                $alternatives[(int) $originalId] = (int) $alternativeId;
            }
        }

        return $alternatives;
    }

    /** @param array<int, int> $alternatives */
    public function saveAlternatives(Session $session, int $familyId, array $alternatives): void
    {
        $session->put($this->alternativesKey($familyId), $alternatives);
    }

    /** @return array<string, mixed>|null */
    public function generated(Session $session, int $familyId): ?array
    {
        $value = $session->get($this->generatedKey($familyId));
        if ( ! is_array($value)) {
            return null;
        }

        $presentation = [];
        foreach ($value as $key => $item) {
            if ( ! is_string($key)) {
                return null;
            }
            $presentation[$key] = $item;
        }

        return $presentation;
    }

    private function key(int $familyId): string
    {
        return "meal_planning.simple_plan.{$familyId}";
    }

    private function generatedKey(int $familyId): string
    {
        return "meal_planning.generated.{$familyId}";
    }

    private function alternativesKey(int $familyId): string
    {
        return "meal_planning.alternatives.{$familyId}";
    }
}
