// database/migrations/2025_09_04_000001_add_claim_and_move_columns_to_tickets.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->foreignId('claimed_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $table->timestamp('claimed_at')->nullable()->after('claimed_by');

            $table->foreignId('last_moved_by')->nullable()->after('claimed_at')->constrained('users')->nullOnDelete();
            $table->timestamp('last_moved_at')->nullable()->after('last_moved_by');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('claimed_by');
            $table->dropColumn('claimed_at');
            $table->dropConstrainedForeignId('last_moved_by');
            $table->dropColumn('last_moved_at');
        });
    }
};
