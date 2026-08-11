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
        Schema::table('ingredients', function (Blueprint $table): void {
            $table->unique(['family_id', 'id']);
        });

        Schema::create('ingredient_alternatives', function (Blueprint $table): void {
            $table->foreignId('family_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('lower_ingredient_id');
            $table->unsignedBigInteger('higher_ingredient_id');
            $table->primary(['lower_ingredient_id', 'higher_ingredient_id']);
            $table->foreign(['family_id', 'lower_ingredient_id'])->references(['family_id', 'id'])->on('ingredients')->cascadeOnDelete();
            $table->foreign(['family_id', 'higher_ingredient_id'])->references(['family_id', 'id'])->on('ingredients')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ingredient_alternatives');
        Schema::table('ingredients', function (Blueprint $table): void {
            $table->dropUnique(['family_id', 'id']);
        });
    }
};
