<?php

declare(strict_types=1);

namespace App\Cookbook\Listeners;

use App\Cookbook\Services\EntityMediaStorage;
use App\FamilyAccess\Events\FamilyDeleted;

final readonly class DeleteFamilyMedia
{
    public function __construct(private EntityMediaStorage $entityMediaStorage) {}

    public function handle(FamilyDeleted $event): void
    {
        $this->entityMediaStorage->deleteFamily($event->familyId);
    }
}
