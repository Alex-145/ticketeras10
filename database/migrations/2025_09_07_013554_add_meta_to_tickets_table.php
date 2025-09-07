<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $t) {
            $t->string('priority', 20)->default('normal')->after('status'); // low|normal|high|urgent
            $t->string('kind', 30)->default('consulta')->after('priority'); // error|consulta|capacitacion
            $t->timestamp('first_response_at')->nullable()->after('updated_at');
        });
    }
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $t) {
            $t->dropColumn(['priority', 'kind', 'first_response_at']);
        });
    }
};
