<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->string('device_identifier', 255);
            $table->string('device_name', 100)->nullable();
            $table->string('platform', 20);
            $table->text('push_token')->nullable();
            $table->timestampTz('last_active_at')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestampTz('revoked_at')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete();
            $table->index('user_id');
        });

        DB::statement("ALTER TABLE devices ADD CONSTRAINT devices_platform_check CHECK (platform IN ('ios', 'android', 'web', 'desktop'))");
        DB::statement("ALTER TABLE devices ADD CONSTRAINT devices_identifier_check CHECK (LENGTH(TRIM(device_identifier)) > 0)");
        DB::statement('CREATE UNIQUE INDEX devices_one_primary_per_user ON devices (user_id) WHERE is_primary = TRUE');
        DB::statement('CREATE UNIQUE INDEX devices_active_identifier_unique ON devices (user_id, device_identifier) WHERE revoked_at IS NULL');
        DB::statement('CREATE INDEX devices_user_active_idx ON devices (user_id, last_active_at DESC) WHERE revoked_at IS NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
