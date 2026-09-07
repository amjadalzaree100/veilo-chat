<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('refresh_tokens', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('device_id');
            $table->uuid('family_id');
            $table->string('token_hash', 255)->unique();
            $table->uuid('replaced_by')->nullable();
            $table->timestampTz('expires_at');
            $table->timestampTz('revoked_at')->nullable();
            $table->string('revoked_reason', 50)->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('device_id')->references('id')->on('devices')->restrictOnDelete();
            $table->foreign('replaced_by')->references('id')->on('refresh_tokens')->restrictOnDelete();
            $table->index('user_id');
            $table->index('device_id');
            $table->index('family_id');
        });

        DB::statement('ALTER TABLE refresh_tokens ADD CONSTRAINT refresh_tokens_expiry_check CHECK (expires_at > created_at)');
        DB::statement('ALTER TABLE refresh_tokens ADD CONSTRAINT refresh_tokens_self_replace_check CHECK (replaced_by IS NULL OR replaced_by <> id)');
        DB::statement('ALTER TABLE refresh_tokens ADD CONSTRAINT refresh_tokens_revocation_check CHECK ((revoked_at IS NULL AND revoked_reason IS NULL) OR (revoked_at IS NOT NULL AND revoked_reason IS NOT NULL))');
        DB::statement('CREATE INDEX refresh_tokens_active_idx ON refresh_tokens (user_id, expires_at) WHERE revoked_at IS NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('refresh_tokens');
    }
};
