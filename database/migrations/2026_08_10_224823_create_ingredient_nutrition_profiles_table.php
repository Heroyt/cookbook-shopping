<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::statement(<<<'SQL'
                CREATE TABLE "ingredient_nutrition_profiles" (
                    "ingredient_id" integer primary key not null,
                    "basis_kind" varchar not null,
                    "basis_quantity" decimal(20, 6) not null,
                    "energy_kcal" decimal(20, 6) not null,
                    "fat_grams" decimal(20, 6) not null,
                    "protein_grams" decimal(20, 6) not null,
                    "carbohydrate_grams" decimal(20, 6) not null,
                    "created_at" datetime null,
                    "updated_at" datetime null,
                    constraint "ingredient_nutrition_profiles_ingredient_foreign" foreign key ("ingredient_id") references "ingredients" ("id") on delete cascade,
                    constraint "ingredient_nutrition_profiles_values_check" check (
                        "basis_kind" in ('package', 'grams', 'millilitres', 'piece')
                        and "basis_quantity" > 0
                        and ("basis_kind" <> 'package' or "basis_quantity" = 1)
                        and "energy_kcal" >= 0 and "fat_grams" >= 0
                        and "protein_grams" >= 0 and "carbohydrate_grams" >= 0
                    )
                )
                SQL);

            return;
        }

        Schema::create('ingredient_nutrition_profiles', function (Blueprint $table): void {
            $table->foreignId('ingredient_id')->primary()->constrained()->cascadeOnDelete();
            $table->string('basis_kind');
            $table->decimal('basis_quantity', total: 20, places: 6);
            $table->decimal('energy_kcal', total: 20, places: 6);
            $table->decimal('fat_grams', total: 20, places: 6);
            $table->decimal('protein_grams', total: 20, places: 6);
            $table->decimal('carbohydrate_grams', total: 20, places: 6);
            $table->timestamps();
        });
        DB::statement(<<<'SQL'
            ALTER TABLE `ingredient_nutrition_profiles`
            ADD CONSTRAINT `ingredient_nutrition_profiles_values_check` CHECK (
                `basis_kind` in ('package', 'grams', 'millilitres', 'piece')
                and `basis_quantity` > 0
                and (`basis_kind` <> 'package' or `basis_quantity` = 1)
                and `energy_kcal` >= 0 and `fat_grams` >= 0
                and `protein_grams` >= 0 and `carbohydrate_grams` >= 0
            )
            SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ingredient_nutrition_profiles');
    }
};
