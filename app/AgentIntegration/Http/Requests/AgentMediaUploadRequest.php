<?php

declare(strict_types=1);

namespace App\AgentIntegration\Http\Requests;

use App\Cookbook\Validation\EntityMediaUploadRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Validation\Validator;
use LogicException;

final class AgentMediaUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(EntityMediaUploadRules $rules): array
    {
        return $rules->rules();
    }

    /** @return list<callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            foreach (array_keys(Arr::except($this->all(), ['image'])) as $field) {
                $validator->errors()->add((string) $field, 'The field is not supported.');
            }
        }];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'image.required' => 'The image field is required.',
            'image.file' => 'The image must be an uploaded file.',
            'image.extensions' => 'The image must have a JPG, JPEG, PNG, or WEBP extension.',
            'image.mimetypes' => 'The image must be a JPEG, PNG, or static WebP file.',
            'image.max' => 'The image must not be larger than 5 MB.',
        ];
    }

    public function uploadedImage(): UploadedFile
    {
        return $this->file('image') ?? throw new LogicException('A validated image upload is required.');
    }
}
