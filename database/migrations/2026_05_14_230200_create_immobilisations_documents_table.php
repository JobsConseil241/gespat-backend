<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('immobilisations_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('immobilisation_id')->constrained('immobilisations')->cascadeOnDelete();
            $table->enum('type', ['facture', 'garantie', 'manuel', 'pv_reception', 'pv_reforme', 'autre'])->default('autre');
            $table->string('path', 500);
            $table->string('libelle', 255);
            $table->unsignedInteger('taille_octets')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('immobilisations_documents');
    }
};
