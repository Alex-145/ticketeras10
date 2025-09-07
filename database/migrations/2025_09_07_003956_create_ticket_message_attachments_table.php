<?php
// database/migrations/2025_09_06_000002_create_ticket_message_attachments_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ticket_message_attachments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('message_id')->constrained('ticket_messages')->cascadeOnDelete();
            $t->string('path');            // storage path
            $t->string('original_name');
            $t->string('mime', 100)->nullable();
            $t->unsignedBigInteger('size')->nullable();
            $t->unsignedInteger('width')->nullable();
            $t->unsignedInteger('height')->nullable();
            $t->timestamps();

            $t->index('message_id');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('ticket_message_attachments');
    }
};
