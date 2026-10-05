<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('thread_id')->constrained('email_threads')->cascadeOnDelete();
            $table->string('gmail_id', 64)->unique();
            $table->enum('direction', ['in', 'out']);
            $table->string('sender_name')->nullable();
            $table->mediumText('body_text')->nullable();
            $table->string('message_id_header', 500)->nullable();
            $table->dateTime('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_messages');
    }
};
