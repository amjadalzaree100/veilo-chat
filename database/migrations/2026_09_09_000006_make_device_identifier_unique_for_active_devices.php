<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('DROP INDEX IF EXISTS devices_one_primary_per_user');
        DB::statement('CREATE UNIQUE INDEX devices_one_primary_per_user ON devices (user_id) WHERE is_primary = TRUE');
        DB::statement('ALTER TABLE devices DROP CONSTRAINT IF EXISTS devices_user_id_device_identifier_unique');
        DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS devices_active_identifier_unique ON devices (user_id, device_identifier) WHERE revoked_at IS NULL');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS devices_active_identifier_unique');
        DB::statement('CREATE UNIQUE INDEX devices_user_id_device_identifier_unique ON devices (user_id, device_identifier)');
    }
};
