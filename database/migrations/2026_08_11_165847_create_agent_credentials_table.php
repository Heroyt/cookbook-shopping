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
        Schema::create('agent_credentials', function (Blueprint $table): void {
            $table->id();
            $table->morphs('tokenable');
            $table->foreignId('family_id')->constrained()->cascadeOnDelete();
            $table->string('issuer_name');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('revoked_at')->nullable()->index();
            $table->foreignId('revoked_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('revocation_reason')->nullable();
            $table->foreignId('rotated_to_id')->nullable()->constrained('agent_credentials')->nullOnDelete();
            $table->timestamps();

            $table->index(['family_id', 'revoked_at', 'created_at']);
            $table->index(['tokenable_type', 'tokenable_id', 'family_id', 'revoked_at'], 'agent_credentials_authority_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_credentials');
    }
};
