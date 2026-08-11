<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ingredients', function (Blueprint $table): void {
            $table->foreignId('store_id')->nullable()->after('family_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('store_section_id')->nullable()->after('store_id');
            $table->foreign(['store_id', 'store_section_id'])
                ->references(['store_id', 'store_section_id'])
                ->on('store_store_section')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ingredients', function (Blueprint $table): void {
            $table->dropForeign(['store_id', 'store_section_id']);
            $table->dropConstrainedForeignId('store_id');
            $table->dropColumn('store_section_id');
        });
    }
};
