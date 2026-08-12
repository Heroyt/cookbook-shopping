<?php

declare(strict_types=1);

namespace App\Cookbook\Validation;

use Illuminate\Contracts\Validation\ValidationRule;

final readonly class EntityMediaUploadRules
{
    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        $maximumKilobytes = config('media.max_kilobytes');

        return [
            'image' => [
                'required',
                'file',
                'extensions:jpg,jpeg,png',
                'mimetypes:image/jpeg,image/png',
                'max:' . (is_int($maximumKilobytes) ? $maximumKilobytes : 5120),
            ],
        ];
    }
}
