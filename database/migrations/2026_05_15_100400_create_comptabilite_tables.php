<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('imports_comptables', function (Blueprint $table) {
            $table->id();
            $table->enum('source', ['sage', 'dolibarr', 'manuel', 'excel', 'csv'])->default('excel');
            $table->string('fichier_source_path', 500)->nullable();
            $table->unsignedSmallInteger('exercice');
            $table->timestamp('date_import')->useCurrent();
            $table->foreignId('importe_par')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('statut', ['en_attente', 'traite', 'echec'])->default('en_attente');
            $table->unsignedInteger('nombre_lignes')->default(0);
            $table->unsignedInteger('nombre_lignes_valides')->default(0);
            $table->unsignedInteger('nombre_lignes_rejetees')->default(0);
            $table->timestamps();

            $table->index('exercice');
            $table->index('statut');
        });

        Schema::create('lignes_comptables_immobilisations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_id')->constrained('imports_comptables')->cascadeOnDelete();
            $table->string('numero_immobilisation_compta', 100)->nullable();
            $table->string('libelle', 255)->nullable();
            $table->string('compte', 20)->nullable();
            $table->date('date_acquisition')->nullable();
            $table->decimal('valeur_origine', 15, 2)->default(0);
            $table->decimal('cumul_amortissement', 15, 2)->default(0);
            $table->decimal('vnc', 15, 2)->default(0);
            $table->string('service_compta', 100)->nullable();
            $table->string('localisation_compta', 200)->nullable();
            $table->foreignId('immobilisation_id')->nullable()->constrained('immobilisations')->nullOnDelete();
            $table->enum('statut_matching', ['non_traite', 'matche_auto', 'matche_manuel', 'non_trouve', 'multiple'])
                ->default('non_traite');
            $table->unsignedTinyInteger('score_matching')->nullable();
            $table->timestamps();

            $table->index('statut_matching');
            $table->index('numero_immobilisation_compta', 'lci_num_immo_compta_index');
        });

        Schema::create('etats_reconciliation', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('exercice');
            $table->date('date_arrete');
            $table->decimal('valeur_brute_compta', 18, 2)->default(0);
            $table->decimal('valeur_brute_patrimoine', 18, 2)->default(0);
            $table->decimal('ecart', 18, 2)->default(0);
            $table->unsignedInteger('nombre_lignes_compta')->default(0);
            $table->unsignedInteger('nombre_immo_patrimoine')->default(0);
            $table->foreignId('genere_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('genere_le')->useCurrent();
            $table->string('pdf_path', 500)->nullable();
            $table->timestamps();

            $table->unique(['exercice', 'date_arrete']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('etats_reconciliation');
        Schema::dropIfExists('lignes_comptables_immobilisations');
        Schema::dropIfExists('imports_comptables');
    }
};
