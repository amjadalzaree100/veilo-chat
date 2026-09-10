<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('conversation_id');
            $table->uuid('sender_id');
            $table->unsignedBigInteger('sequence_no');
            $table->string('client_message_id', 255)->nullable();
            $table->uuid('reply_to_message_id')->nullable();
            $table->text('body_ciphertext');
            $table->unsignedInteger('encryption_key_version');
            $table->timestampTz('edited_at')->nullable();
            $table->timestampTz('deleted_at')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->foreign('conversation_id')->references('id')->on('conversations')->cascadeOnDelete();
            $table->foreign('sender_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('reply_to_message_id')->references('id')->on('messages')->nullOnDelete();
            $table->unique(['conversation_id', 'sequence_no']);
            $table->index(['conversation_id', 'created_at']);
            $table->index(['sender_id', 'created_at']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('CREATE UNIQUE INDEX messages_client_id_unique ON messages (conversation_id, sender_id, client_message_id) WHERE client_message_id IS NOT NULL');
            DB::statement('CREATE INDEX messages_active_conversation_idx ON messages (conversation_id, sequence_no DESC) WHERE deleted_at IS NULL');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
