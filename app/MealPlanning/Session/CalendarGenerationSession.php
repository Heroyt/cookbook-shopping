<?php

declare(strict_types=1);

namespace App\MealPlanning\Session;

use Illuminate\Contracts\Session\Session;

final class CalendarGenerationSession
{
    /** @param list<string> $dates */
    public function selectDates(Session $session, int $familyId, array $dates): bool
    {
        $sameSelection = $dates === $this->dates($session, $familyId);
        $this->saveDates($session, $familyId, $dates);
        $session->forget($this->generatedKey($familyId));
        if ( ! $sameSelection) {
            $session->forget($this->alternativesKey($familyId));
        }

        return $sameSelection;
    }

    /** @param list<string> $dates */
    public function saveDates(Session $session, int $familyId, array $dates): void
    {
        $session->put($this->datesKey($familyId), $dates);
    }

    /** @return list<string> */
    public function dates(Session $session, int $familyId): array
    {
        $value = $session->get($this->datesKey($familyId), []);
        if ( ! is_array($value)) {
            return [];
        }

        return array_values(array_filter($value, static fn (mixed $date): bool => is_string($date)));
    }

    /** @param array<string, mixed> $presentation */
    public function saveGenerated(Session $session, int $familyId, array $presentation): void
    {
        $session->put($this->generatedKey($familyId), $presentation);
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

    private function datesKey(int $familyId): string
    {
        return "meal_planning.calendar.dates.{$familyId}";
    }

    private function generatedKey(int $familyId): string
    {
        return "meal_planning.calendar.generated.{$familyId}";
    }

    private function alternativesKey(int $familyId): string
    {
        return "meal_planning.calendar.alternatives.{$familyId}";
    }
}
