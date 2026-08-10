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
        Schema::table('ingredients', function (Blueprint $table): void {
            $table->unique(['family_id', 'id']);
        });

        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::statement(<<<'SQL'
                CREATE TABLE "ingredient_alternatives" (
                    "family_id" integer not null,
                    "lower_ingredient_id" integer not null,
                    "higher_ingredient_id" integer not null,
                    primary key ("lower_ingredient_id", "higher_ingredient_id"),
                    constraint "ingredient_alternatives_family_foreign" foreign key ("family_id") references "families" ("id") on delete cascade,
                    constraint "ingredient_alternatives_lower_foreign" foreign key ("family_id", "lower_ingredient_id") references "ingredients" ("family_id", "id") on delete cascade,
                    constraint "ingredient_alternatives_higher_foreign" foreign key ("family_id", "higher_ingredient_id") references "ingredients" ("family_id", "id") on delete cascade,
                    constraint "ingredient_alternatives_order_check" check ("lower_ingredient_id" < "higher_ingredient_id")
                )
                SQL);

            return;
        }

        Schema::create('ingredient_alternatives', function (Blueprint $table): void {
            $table->foreignId('family_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('lower_ingredient_id');
            $table->unsignedBigInteger('higher_ingredient_id');
            $table->primary(['lower_ingredient_id', 'higher_ingredient_id']);
            $table->foreign(['family_id', 'lower_ingredient_id'])->references(['family_id', 'id'])->on('ingredients')->cascadeOnDelete();
            $table->foreign(['family_id', 'higher_ingredient_id'])->references(['family_id', 'id'])->on('ingredients')->cascadeOnDelete();
        });
        DB::statement('ALTER TABLE `ingredient_alternatives` ADD CONSTRAINT `ingredient_alternatives_order_check` CHECK (`lower_ingredient_id` < `higher_ingredient_id`)');
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
