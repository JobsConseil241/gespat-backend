<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mouvements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('immobilisation_id')->constrained('immobilisations')->cascadeOnDelete();
            $table->enum('type_mouvement', [
                'entree', 'transfert', 'sortie_temporaire', 'retour',
                'changement_etat', 'maintenance_entree', 'maintenance_sortie',
                'reforme', 'cession', 'perte',
            ]);
            $table->date('date_mouvement');
            $table->text('motif')->nullable();

            $table->foreignId('site_origine_id')->nullable()->constrained('sites')->nullOnDelete();
            $table->foreignId('localisation_origine_id')->nullable()->constrained('localisations')->nullOnDelete();
            $table->foreignId('service_origine_id')->nullable()->constrained('services')->nullOnDelete();

            $table->foreignId('site_destination_id')->nullable()->constrained('sites')->nullOnDelete();
            $table->foreignId('localisation_destination_id')->nullable()->constrained('localisations')->nullOnDelete();
            $table->foreignId('service_destination_id')->nullable()->constrained('services')->nullOnDelete();

            $table->string('document_path', 500)->nullable();

            $table->enum('statut', ['propose', 'valide', 'execute', 'refuse', 'annule'])->default('propose');
            $table->foreignId('valide_par_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('valide_at')->nullable();

            $table->foreignId('cree_par_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['immobilisation_id', 'date_mouvement']);
            $table->index('type_mouvement');
            $table->index('statut');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mouvements');
    }
};
