<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_threads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mailbox_id')->constrained()->cascadeOnDelete();
            $table->string('gmail_thread_id', 64);
            $table->string('customer_name')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('subject', 500)->nullable();
            $table->string('snippet', 300)->nullable();
            $table->dateTime('last_message_at')->nullable();
            $table->boolean('unread')->default(true);
            $table->timestamps();
            $table->unique(['mailbox_id', 'gmail_thread_id']);
            $table->index('last_message_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_threads');
    }
};
