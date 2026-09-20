<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('conversation_id');
            $table->uuid('sender_id');
            $table->unsignedBigInteger('sequence_no');
            $table->uuid('client_message_id')->nullable();
            $table->uuid('reply_to_message_id')->nullable();
            $table->text('body_ciphertext');
            $table->unsignedInteger('encryption_key_version')->default(1);
            $table->timestampTz('edited_at')->nullable();
            $table->timestampTz('deleted_at')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->foreign('conversation_id')->references('id')->on('conversations')->cascadeOnDelete();
            $table->foreign('sender_id')->references('id')->on('users')->restrictOnDelete();

            $table->unique(['conversation_id', 'sequence_no']);
            $table->unique(['id', 'conversation_id']); 
            $table->index(['conversation_id', 'created_at']);
            $table->index(['sender_id', 'created_at']);
        });

        Schema::table('messages', function (Blueprint $table): void {
            $table->foreign('reply_to_message_id')
                ->references('id')
                ->on('messages')
                ->nullOnDelete();
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('CREATE UNIQUE INDEX messages_sender_client_id_unique ON messages (sender_id, client_message_id) WHERE client_message_id IS NOT NULL');
            DB::statement('CREATE INDEX messages_conversation_cursor_idx ON messages (conversation_id, sequence_no DESC, id DESC)');
            DB::statement('CREATE INDEX messages_reply_to_idx ON messages (reply_to_message_id) WHERE reply_to_message_id IS NOT NULL');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
