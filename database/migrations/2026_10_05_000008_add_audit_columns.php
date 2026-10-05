<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('email_messages', function (Blueprint $table) {
            // Which portal user sent this reply (null = sent from Gmail itself).
            $table->foreignId('sent_by_user_id')->nullable()->after('sender_name')
                ->constrained('users')->nullOnDelete();
        });

        Schema::create('thread_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('thread_id')->constrained('email_threads')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->dateTime('read_at');
            $table->unique(['thread_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::table('email_messages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sent_by_user_id');
        });
        Schema::dropIfExists('thread_reads');
    }
};
