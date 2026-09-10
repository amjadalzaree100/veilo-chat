<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('user_low_id');
            $table->uuid('user_high_id');
            $table->unsignedBigInteger('low_last_delivered_sequence')->default(0);
            $table->unsignedBigInteger('low_last_read_sequence')->default(0);
            $table->unsignedBigInteger('high_last_delivered_sequence')->default(0);
            $table->unsignedBigInteger('high_last_read_sequence')->default(0);
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->foreign('user_low_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('user_high_id')->references('id')->on('users')->restrictOnDelete();
            $table->unique(['user_low_id', 'user_high_id']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE conversations ADD CONSTRAINT conversations_users_ordered_check CHECK (user_low_id < user_high_id)');
            DB::statement('CREATE INDEX conversations_user_low_idx ON conversations (user_low_id, updated_at DESC)');
            DB::statement('CREATE INDEX conversations_user_high_idx ON conversations (user_high_id, updated_at DESC)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
