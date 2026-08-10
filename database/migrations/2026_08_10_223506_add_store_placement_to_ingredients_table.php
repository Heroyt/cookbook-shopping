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
            $this->rebuildSqliteIngredients(withPlacement: true);

            return;
        }

        Schema::table('ingredients', function (Blueprint $table): void {
            $table->foreignId('store_id')->nullable()->after('family_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('store_section_id')->nullable()->after('store_id');
            $table->foreign(['store_id', 'store_section_id'])
                ->references(['store_id', 'store_section_id'])
                ->on('store_store_section')
                ->restrictOnDelete();
        });

        DB::statement(<<<'SQL'
            ALTER TABLE `ingredients`
            ADD CONSTRAINT `ingredients_store_section_requires_store_check`
            CHECK (`store_section_id` IS NULL OR `store_id` IS NOT NULL)
            SQL);
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            $this->rebuildSqliteIngredients(withPlacement: false);

            return;
        }

        DB::statement('ALTER TABLE `ingredients` DROP CONSTRAINT `ingredients_store_section_requires_store_check`');

        Schema::table('ingredients', function (Blueprint $table): void {
            $table->dropForeign(['store_id', 'store_section_id']);
            $table->dropConstrainedForeignId('store_id');
            $table->dropColumn('store_section_id');
        });
    }

    private function rebuildSqliteIngredients(bool $withPlacement): void
    {
        Schema::disableForeignKeyConstraints();

        $placementColumns = $withPlacement
            ? '"store_id" integer null, "store_section_id" integer null,'
            : '';
        $placementConstraints = $withPlacement
            ? ', constraint "ingredients_store_id_foreign" foreign key ("store_id") references "stores" ("id"), constraint "ingredients_store_placement_foreign" foreign key ("store_id", "store_section_id") references "store_store_section" ("store_id", "store_section_id"), constraint "ingredients_store_section_requires_store_check" check ("store_section_id" is null or "store_id" is not null)'
            : '';

        DB::statement(<<<SQL
            CREATE TABLE "ingredients_rebuilt" (
                "id" integer primary key autoincrement not null,
                "family_id" integer not null,
                {$placementColumns}
                "name" varchar not null,
                "normalized_name" blob not null,
                "weight_grams" decimal(20, 6) null,
                "volume_millilitres" decimal(20, 6) null,
                "piece_count" decimal(20, 6) null,
                "description" text null,
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
                {$placementConstraints}
            )
            SQL);

        $placementTargets = $withPlacement ? ', "store_id", "store_section_id"' : '';
        $placementValues = $withPlacement ? ', null, null' : '';
        DB::statement(<<<SQL
            INSERT INTO "ingredients_rebuilt" (
                "id", "family_id", "name", "normalized_name", "weight_grams", "volume_millilitres", "piece_count", "description", "created_at", "updated_at"{$placementTargets}
            )
            SELECT
                "id", "family_id", "name", "normalized_name", "weight_grams", "volume_millilitres", "piece_count", "description", "created_at", "updated_at"{$placementValues}
            FROM "ingredients"
            SQL);
        Schema::drop('ingredients');
        Schema::rename('ingredients_rebuilt', 'ingredients');
        DB::statement('CREATE UNIQUE INDEX "ingredients_family_id_normalized_name_unique" ON "ingredients" ("family_id", "normalized_name")');

        Schema::enableForeignKeyConstraints();
    }
};
