<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('amortissements_annuels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('immobilisation_id')->constrained('immobilisations')->cascadeOnDelete();
            $table->unsignedSmallInteger('exercice');
            $table->unsignedTinyInteger('mois')->nullable()->comment('Si calcul mensuel');

            $table->decimal('valeur_brute_debut', 15, 2);
            $table->decimal('cumul_amortissement_debut', 15, 2);
            $table->decimal('vnc_debut', 15, 2);
            $table->decimal('dotation_exercice', 15, 2);
            $table->decimal('cumul_amortissement_fin', 15, 2);
            $table->decimal('vnc_fin', 15, 2);

            $table->enum('methode_utilisee', ['lineaire', 'degressif', 'unite_oeuvre']);
            $table->boolean('est_valide')->default(false);
            $table->foreignId('valide_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('valide_le')->nullable();
            $table->timestamps();

            $table->unique(['immobilisation_id', 'exercice', 'mois']);
            $table->index('exercice');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('amortissements_annuels');
    }
};
