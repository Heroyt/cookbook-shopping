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
        Schema::create('agent_change_sets', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignId('family_id')->constrained()->cascadeOnDelete();
            $table->foreignId('agent_credential_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('issuer_user_id')->nullable();
            $table->string('issuer_name');
            $table->string('credential_name');
            $table->string('client_request_id');
            $table->string('status')->index();
            $table->char('digest', 64);
            $table->unsignedSmallInteger('document_version')->default(1);
            $table->json('canonical_request');
            $table->json('preview_document');
            $table->json('warning_acknowledgements')->nullable();
            $table->json('identifier_mappings')->nullable();
            $table->json('result_document')->nullable();
            $table->json('resource_types');
            $table->string('outcome')->nullable()->index();
            $table->string('title')->nullable();
            $table->json('source_urls');
            $table->text('note')->nullable();
            $table->foreignUlid('supersedes_id')->nullable()->constrained('agent_change_sets')->nullOnDelete();
            $table->unsignedInteger('payload_bytes');
            $table->unsignedInteger('operation_count');
            $table->timestamp('expires_at')->index();
            $table->timestamp('applied_at')->nullable();
            $table->timestamp('terminal_at')->nullable()->index();
            $table->timestamps();

            $table->unique(['agent_credential_id', 'client_request_id']);
            $table->index(['family_id', 'status', 'created_at']);
            $table->index(['family_id', 'agent_credential_id', 'created_at'], 'agent_change_sets_credential_history_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_change_sets');
    }
};
