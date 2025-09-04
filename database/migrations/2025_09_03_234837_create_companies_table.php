<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();

            $table->string('name');                 // nombre
            $table->string('ruc', 11)->nullable();  // RUC (PE: 11 dígitos)
            $table->string('phone', 30)->nullable(); // celular / teléfono
            $table->string('logo_path')->nullable(); // ruta en storage
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->unique('ruc');      // opcional: único (permite múltiples NULL)
            $table->index('name');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
