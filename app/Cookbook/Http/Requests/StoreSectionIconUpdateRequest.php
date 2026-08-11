<?php

declare(strict_types=1);

namespace App\Cookbook\Http\Requests;

use App\Cookbook\Values\StoreSectionIcon;
use App\Http\Requests\AuthenticatedRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

final class StoreSectionIconUpdateRequest extends AuthenticatedRequest
{
    public function storeSectionIcon(): StoreSectionIcon
    {
        return StoreSectionIcon::from($this->string('icon')->toString());
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'icon' => ['required', Rule::enum(StoreSectionIcon::class)],
        ];
    }
}
