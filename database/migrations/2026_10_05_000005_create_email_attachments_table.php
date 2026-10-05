<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained('email_messages')->cascadeOnDelete();
            $table->string('gmail_message_id', 64);
            $table->text('attachment_id');
            $table->string('filename');
            $table->string('mime_type', 150)->default('application/octet-stream');
            $table->unsignedBigInteger('size')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_attachments');
    }
};
