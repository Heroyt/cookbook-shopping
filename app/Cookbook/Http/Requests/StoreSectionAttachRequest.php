<?php

declare(strict_types=1);

namespace App\Cookbook\Http\Requests;

use App\Http\Requests\AuthenticatedRequest;
use Illuminate\Contracts\Validation\ValidationRule;

final class StoreSectionAttachRequest extends AuthenticatedRequest
{
    public function storeSectionId(): int
    {
        return $this->integer('store_section_id');
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'store_section_id' => ['required', 'integer', 'min:1'],
        ];
    }
}
