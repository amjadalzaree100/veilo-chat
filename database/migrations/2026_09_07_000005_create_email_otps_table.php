<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_otps', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->nullable();
            $table->string('email', 255);
            $table->string('purpose', 30);
            $table->string('code_hash', 255);
            $table->smallInteger('attempts')->default(0);
            $table->smallInteger('max_attempts')->default(5);
            $table->timestampTz('expires_at');
            $table->timestampTz('consumed_at')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->index(['user_id', 'created_at']);
        });

        DB::statement('ALTER TABLE email_otps ADD CONSTRAINT email_otps_attempts_check CHECK (attempts >= 0)');
        DB::statement('ALTER TABLE email_otps ADD CONSTRAINT email_otps_max_attempts_check CHECK (max_attempts > 0)');
        DB::statement('ALTER TABLE email_otps ADD CONSTRAINT email_otps_attempt_limit_check CHECK (attempts <= max_attempts)');
        DB::statement("ALTER TABLE email_otps ADD CONSTRAINT email_otps_purpose_check CHECK (purpose IN ('link_email', 'login', 'recovery'))");
        DB::statement('ALTER TABLE email_otps ADD CONSTRAINT email_otps_expiry_check CHECK (expires_at > created_at)');
        DB::statement('CREATE INDEX email_otps_email_idx ON email_otps (LOWER(email), created_at DESC)');
        DB::statement('CREATE INDEX email_otps_active_idx ON email_otps (LOWER(email), purpose, expires_at) WHERE consumed_at IS NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('email_otps');
    }
};
