<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('biens_immobiliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('immobilisation_id')->unique()->constrained('immobilisations')->cascadeOnDelete();
            $table->decimal('superficie_terrain_m2', 12, 2)->nullable();
            $table->decimal('superficie_batie_m2', 12, 2)->nullable();
            $table->unsignedSmallInteger('nombre_niveaux')->nullable();
            $table->unsignedSmallInteger('annee_construction')->nullable();
            $table->string('titre_foncier', 100)->nullable();
            $table->string('numero_cadastre', 100)->nullable();
            $table->enum('usage', ['administratif', 'residentiel', 'technique', 'mixte', 'autre'])->default('administratif');
            $table->timestamps();

            $table->index('titre_foncier');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('biens_immobiliers');
    }
};
