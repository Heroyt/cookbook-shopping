<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('family_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('recipe_id');
            $table->date('date');
            $table->enum('meal_label_key', [
                'breakfast',
                'morning_snack',
                'lunch',
                'afternoon_snack',
                'dinner',
                'unlabeled',
            ]);
            $table->decimal('serving_count', total: 20, places: 6);
            $table->timestamps();

            $table->foreign(['family_id', 'recipe_id'])
                ->references(['family_id', 'id'])
                ->on('recipes')
                ->cascadeOnDelete();
            $table->unique(['family_id', 'date', 'meal_label_key', 'recipe_id']);
            $table->index(['family_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_entries');
    }
};
