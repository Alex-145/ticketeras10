<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('applicant_aliases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('applicant_id')
                ->constrained('applicants')
                ->cascadeOnUpdate()
                ->cascadeOnDelete(); // si borras el solicitante, se borran los alias
            $table->string('alias', 100);
            $table->timestamps();

            $table->unique(['applicant_id', 'alias']); // único por solicitante
            $table->index('alias');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applicant_aliases');
    }
};
