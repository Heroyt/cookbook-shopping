<?php

declare(strict_types=1);

namespace App\Cookbook\Http\Requests;

final class StoreSectionUpdateRequest extends StoreSectionWriteRequest
{
    protected function iconPresenceRule(): string
    {
        return 'required';
    }
}
