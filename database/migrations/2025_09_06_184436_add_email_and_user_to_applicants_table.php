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
        Schema::table('applicants', function (Blueprint $table) {
            // email único para identificar y crear cuenta
            if (!Schema::hasColumn('applicants', 'email')) {
                $table->string('email')->unique()->after('id');
            }
            // vínculo opcional al usuario creado
            if (!Schema::hasColumn('applicants', 'user_id')) {
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete()->after('email');
            }
        });
    }

    public function down(): void
    {
        Schema::table('applicants', function (Blueprint $table) {
            if (Schema::hasColumn('applicants', 'user_id')) {
                $table->dropConstrainedForeignId('user_id');
            }
            if (Schema::hasColumn('applicants', 'email')) {
                $table->dropColumn('email');
            }
        });
    }
};
