<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('immobilisations', function (Blueprint $table) {
            $table->id();

            // Identification
            $table->string('code_inventaire', 100)->unique();
            $table->string('qr_payload', 500)->nullable();
            $table->string('libelle', 200);
            $table->text('description')->nullable();

            // Référentiel
            $table->foreignId('categorie_id')->constrained('categories_immobilisations');
            $table->foreignId('marque_id')->nullable()->constrained('marques')->nullOnDelete();
            $table->foreignId('modele_id')->nullable()->constrained('modeles')->nullOnDelete();
            $table->string('numero_serie', 100)->nullable();
            $table->string('numero_chassis', 100)->nullable();
            $table->string('numero_immatriculation', 30)->nullable();

            // Localisation actuelle
            $table->foreignId('site_id')->constrained('sites');
            $table->foreignId('localisation_id')->nullable()->constrained('localisations')->nullOnDelete();
            $table->foreignId('service_id')->nullable()->constrained('services')->nullOnDelete();
            $table->foreignId('affecte_a_user_id')->nullable()->constrained('users')->nullOnDelete();

            // Acquisition
            $table->date('date_acquisition')->nullable();
            $table->enum('mode_acquisition', ['achat', 'donation', 'transfert', 'production_interne'])->default('achat');
            $table->foreignId('fournisseur_id')->nullable()->constrained('fournisseurs')->nullOnDelete();
            $table->string('numero_facture', 100)->nullable();
            $table->string('numero_bon_commande', 100)->nullable();
            $table->string('numero_bon_livraison', 100)->nullable();

            // Valorisation
            $table->decimal('valeur_acquisition', 15, 2)->default(0);
            $table->decimal('valeur_acquisition_ht', 15, 2)->nullable();
            $table->decimal('tva', 15, 2)->nullable();
            $table->string('devise', 3)->default('XAF');
            $table->decimal('taux_change', 12, 6)->nullable();

            // Comptable
            $table->boolean('est_immobilise_comptablement')->default(true);
            $table->string('compte_immobilisation', 20)->nullable();
            $table->string('numero_immobilisation_compta', 50)->nullable();

            // Amortissement
            $table->unsignedSmallInteger('duree_amortissement_mois')->nullable();
            $table->enum('methode_amortissement', ['lineaire', 'degressif', 'unite_oeuvre', 'non_amortissable'])->default('lineaire');
            $table->date('date_mise_en_service')->nullable();
            $table->decimal('valeur_residuelle', 15, 2)->default(0);

            // État
            $table->enum('etat_physique', ['neuf', 'bon', 'moyen', 'mauvais', 'hors_service'])->default('bon');
            $table->enum('statut', ['actif', 'en_maintenance', 'en_transfert', 'reforme', 'cede', 'perdu', 'vole', 'detruit'])->default('actif');

            // Inventaire (dernier inventaire constaté)
            $table->unsignedBigInteger('dernier_inventaire_id')->nullable()->index();
            $table->timestamp('dernier_inventaire_date')->nullable();

            // Multimédia
            $table->string('photo_principale_path', 500)->nullable();

            // Traçabilité
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['site_id', 'statut']);
            $table->index(['categorie_id', 'statut']);
            $table->index('date_acquisition');
            $table->index('numero_serie');
            $table->index('numero_immatriculation');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('immobilisations');
    }
};
