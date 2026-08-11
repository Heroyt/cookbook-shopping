<?php

declare(strict_types=1);

namespace App\AgentIntegration\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ApplyAgentChangeSetRequest extends FormRequest
{
    public function digest(): string
    {
        $digest = $this->input('digest');

        return is_string($digest) ? $digest : '';
    }

    /** @return list<string> */
    public function warningAcknowledgements(): array
    {
        $acknowledgements = $this->input('warning_acknowledgements', []);
        if ( ! is_array($acknowledgements) || ! array_is_list($acknowledgements)) {
            return [];
        }

        return array_values(array_filter($acknowledgements, is_string(...)));
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'digest' => ['required', 'string', 'size:64', 'regex:/^[a-f0-9]{64}$/'],
            'warning_acknowledgements' => ['present', 'array'],
            'warning_acknowledgements.*' => ['string', 'distinct'],
        ];
    }
}
