<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();

            $table->string('number', 50)->nullable();        // número extraído de la imagen o manual
            $table->string('title')->nullable();             // opcional
            $table->text('description')->nullable();         // opcional

            $table->foreignId('applicant_id')->constrained('applicants')
                ->cascadeOnUpdate()->restrictOnDelete();

            // Redundante pero útil para filtros; si no lo quieres, quítalo.
            $table->foreignId('company_id')->nullable()->constrained('companies')
                ->cascadeOnUpdate()->nullOnDelete();

            // Quedan vacíos inicialmente
            $table->foreignId('module_id')->nullable()->constrained('modules')
                ->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')
                ->cascadeOnUpdate()->nullOnDelete();

            $table->string('status', 16)->default('todo');   // todo | doing | done
            $table->string('image_path')->nullable();        // imagen subida (opcional)

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status']);
            $table->index(['number']);
            $table->index(['applicant_id']);
            $table->index(['company_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
