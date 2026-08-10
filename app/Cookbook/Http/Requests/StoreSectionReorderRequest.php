<?php

declare(strict_types=1);

namespace App\Cookbook\Http\Requests;

use App\Http\Requests\AuthenticatedRequest;
use Illuminate\Contracts\Validation\ValidationRule;

final class StoreSectionReorderRequest extends AuthenticatedRequest
{
    /** @return list<int> */
    public function storeSectionIds(): array
    {
        $storeSectionIds = $this->validated('store_section_ids');

        if ( ! is_array($storeSectionIds)) {
            return [];
        }

        return array_values(array_map(
            static fn (mixed $storeSectionId): int => filter_var($storeSectionId, FILTER_VALIDATE_INT) ?: 0,
            $storeSectionIds,
        ));
    }

    public function version(): int
    {
        return $this->integer('version');
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'store_section_ids' => ['required', 'array'],
            'store_section_ids.*' => ['required', 'integer', 'min:1'],
            'version' => ['required', 'integer', 'min:0'],
        ];
    }
}
