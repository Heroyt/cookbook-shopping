<?php

declare(strict_types=1);

namespace App\FamilyAccess\Models;

use App\Models\User;
use Database\Factories\FamilyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read int $memberships_count
 */
#[Fillable(['name'])]
final class Family extends Model
{
    /** @use HasFactory<FamilyFactory> */
    use HasFactory;

    /** @return HasMany<FamilyMembership, $this> */
    public function memberships(): HasMany
    {
        return $this->hasMany(FamilyMembership::class);
    }

    /** @return BelongsToMany<User, $this> */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'family_memberships')->withTimestamps();
    }

    protected static function newFactory(): FamilyFactory
    {
        return FamilyFactory::new();
    }
}
