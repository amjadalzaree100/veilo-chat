<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->string('username', 50);
            $table->string('display_name', 100)->nullable();
            $table->string('email', 255)->nullable();
            $table->timestampTz('email_verified_at')->nullable();
            $table->text('recovery_secret_encrypted');
            $table->timestampTz('recovery_secret_hidden_at')->nullable();
            $table->string('privacy', 20)->default('public');
            $table->softDeletesTz();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();
        });

        DB::statement("ALTER TABLE users ADD CONSTRAINT users_privacy_check CHECK (privacy IN ('public', 'private'))");
        DB::statement('CREATE UNIQUE INDEX users_username_active_unique ON users (LOWER(username)) WHERE deleted_at IS NULL');
        DB::statement('CREATE UNIQUE INDEX users_email_active_unique ON users (LOWER(email)) WHERE deleted_at IS NULL AND email IS NOT NULL');
        DB::statement('CREATE INDEX users_username_search_idx ON users (LOWER(username)) WHERE deleted_at IS NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
