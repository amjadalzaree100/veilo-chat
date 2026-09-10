<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table): void {
            $table->string('device_secret_hash', 255)->nullable();
        });

        DB::statement('CREATE INDEX IF NOT EXISTS devices_active_identifier_lookup_idx ON devices (device_identifier) WHERE revoked_at IS NULL');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS devices_active_identifier_lookup_idx');

        Schema::table('devices', function (Blueprint $table): void {
            $table->dropColumn('device_secret_hash');
        });
    }
};
