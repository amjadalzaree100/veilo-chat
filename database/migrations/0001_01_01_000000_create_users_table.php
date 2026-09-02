<?php

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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('public_id', 16)->unique();
            $table->string('name_display');
            $table->string('email')->nullable()->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->text('recovery_secret')->nullable();
            $table->timestamp('recovery_secret_hidden_at')->nullable();
            $table->timestamp('recovery_secret_updated_at')->nullable();
            $table->boolean('is_discoverable')->default(true);
            $table->boolean('show_public_id_on_profile')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
