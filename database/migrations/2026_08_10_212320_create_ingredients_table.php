<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::statement(<<<'SQL'
                CREATE TABLE "ingredients" (
                    "id" integer primary key autoincrement not null,
                    "family_id" integer not null,
                    "name" varchar not null,
                    "normalized_name" blob not null,
                    "weight_grams" decimal(20, 6) null,
                    "volume_millilitres" decimal(20, 6) null,
                    "piece_count" decimal(20, 6) null,
                    "created_at" datetime null,
                    "updated_at" datetime null,
                    constraint "ingredients_family_id_foreign" foreign key ("family_id") references "families" ("id") on delete cascade,
                    constraint "ingredients_package_quantities_check" check (
                        ("weight_grams" is null or "weight_grams" > 0)
                        and ("volume_millilitres" is null or "volume_millilitres" > 0)
                        and ("piece_count" is null or "piece_count" > 0)
                        and not ("weight_grams" is not null and "volume_millilitres" is not null)
                        and ("weight_grams" is not null or "volume_millilitres" is not null or "piece_count" is not null)
                    )
                )
                SQL);
            DB::statement('CREATE UNIQUE INDEX "ingredients_family_id_normalized_name_unique" ON "ingredients" ("family_id", "normalized_name")');

            return;
        }

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

        DB::statement(<<<'SQL'
            ALTER TABLE `ingredients`
            ADD CONSTRAINT `ingredients_package_quantities_check` CHECK (
                (`weight_grams` is null or `weight_grams` > 0)
                and (`volume_millilitres` is null or `volume_millilitres` > 0)
                and (`piece_count` is null or `piece_count` > 0)
                and not (`weight_grams` is not null and `volume_millilitres` is not null)
                and (`weight_grams` is not null or `volume_millilitres` is not null or `piece_count` is not null)
            )
            SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('ingredients');
    }
};
