<?php

declare(strict_types=1);

namespace Tests\Unit\Cookbook;

use App\Cookbook\Models\RecipeIngredient;
use App\Cookbook\Models\RecipeStep;
use Tests\TestCase;

final class RecipeFactoryTest extends TestCase
{
    public function test_recipe_child_factories_create_family_consistent_aggregates(): void
    {
        $line = RecipeIngredient::factory()->create();
        $step = RecipeStep::factory()->create();

        $this->assertSame($line->family_id, $line->recipe()->value('family_id'));
        $this->assertSame($line->family_id, $line->ingredient()->value('family_id'));
        $this->assertSame($step->family_id, $step->recipe()->value('family_id'));
    }
}
