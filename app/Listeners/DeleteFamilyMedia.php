<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Cookbook\Services\EntityMediaStorage;
use App\FamilyAccess\Events\FamilyDeleting;

final readonly class DeleteFamilyMedia
{
    public function __construct(private EntityMediaStorage $entityMediaStorage) {}

    public function handle(FamilyDeleting $event): void
    {
        $deletion = $this->entityMediaStorage->deleteFamilyWithBackup($event->familyId);

        $event->onRollback(fn () => $this->entityMediaStorage->restore($deletion));
    }
}
