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
    }

    public function forget(Session $session, int $familyId): void
    {
        $session->forget($this->key($familyId));
    }

    /** @param array<string, mixed> $presentation */
    public function flashGenerated(Session $session, int $familyId, array $presentation): void
    {
        $session->flash($this->generatedKey($familyId), $presentation);
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
}
