<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ingredients', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('family_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->binary('normalized_name', length: 1020);
            $table->decimal('weight_grams', total: 20, places: 6)->nullable();
            $table->decimal('volume_millilitres', total: 20, places: 6)->nullable();
            $table->decimal('piece_count', total: 20, places: 6)->nullable();
            $table->timestamps();

            $table->unique(['family_id', 'normalized_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ingredients');
    }
};
