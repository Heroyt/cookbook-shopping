<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saved_shopping_lists', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('family_id')->constrained()->cascadeOnDelete();
            $table->timestamp('generated_at', precision: 6);
            $table->enum('source_kind', ['simple_plan', 'calendar']);
            $table->unsignedSmallInteger('payload_schema_version');
            $table->json('payload');

            $table->index(['family_id', 'generated_at', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_shopping_lists');
    }
};
