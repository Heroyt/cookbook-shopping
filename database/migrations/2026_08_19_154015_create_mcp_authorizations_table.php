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
        Schema::create('mcp_authorizations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('family_id')->constrained()->cascadeOnDelete();
            $table->uuid('passport_client_id');
            $table->foreignId('agent_credential_id')->unique()->constrained('agent_credentials')->cascadeOnDelete();
            $table->timestamps();

            $table->foreign('passport_client_id')->references('id')->on('oauth_clients')->cascadeOnDelete();
            $table->unique(['user_id', 'passport_client_id']);
            $table->index(['family_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mcp_authorizations');
    }
};
