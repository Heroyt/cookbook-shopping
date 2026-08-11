<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ingredient_nutrition_profiles');
    }
};
