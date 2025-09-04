<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('applicants', function (Blueprint $table) {
            $table->id();
            $table->string('name');                 // nombre del solicitante
            $table->string('phone', 30)->nullable(); // celular
            $table->foreignId('company_id')        // compañía a la que pertenece
                ->constrained('companies')
                ->cascadeOnUpdate()
                ->restrictOnDelete();            // evita borrar company con solicitantes
            $table->timestamps();
            $table->softDeletes();

            $table->index(['name']);
            $table->index(['company_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applicants');
    }
};
