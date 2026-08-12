<?php

declare(strict_types=1);

namespace App\Cookbook\Http\Requests;

use App\Cookbook\Validation\EntityMediaUploadRules;
use App\Http\Requests\AuthenticatedRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;
use LogicException;

final class EntityMediaStoreRequest extends AuthenticatedRequest
{
    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(EntityMediaUploadRules $rules): array
    {
        return $rules->rules();
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'image.extensions' => __('The file must have a JPG, JPEG, or PNG extension.'),
            'image.mimetypes' => __('Select a JPEG or PNG image.'),
            'image.max' => __('The image must not be larger than 5 MB.'),
        ];
    }

    public function uploadedImage(): UploadedFile
    {
        return $this->file('image') ?? throw new LogicException('A validated image upload is required.');
    }
}
