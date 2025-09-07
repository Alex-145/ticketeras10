<?php

// database/migrations/2025_09_06_000001_create_ticket_messages_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ticket_messages', function (Blueprint $t) {
            $t->id();
            $t->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
            // quién envió:
            $t->enum('sender_type', ['applicant', 'staff']);   // staff = users, applicant = applicants
            $t->unsignedBigInteger('sender_id');              // applicant_id o user_id
            $t->text('body')->nullable();
            $t->timestamps();

            $t->index(['ticket_id', 'created_at']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('ticket_messages');
    }
};
