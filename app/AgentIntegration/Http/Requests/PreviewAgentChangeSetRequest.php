<?php

declare(strict_types=1);

namespace App\AgentIntegration\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class PreviewAgentChangeSetRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function document(): array
    {
        $document = [];

        foreach ($this->all() as $key => $value) {
            if (is_string($key)) {
                $document[$key] = $value;
            }
        }

        return $document;
    }

    /** @return array<string, never> */
    public function rules(): array
    {
        return [];
    }
}
