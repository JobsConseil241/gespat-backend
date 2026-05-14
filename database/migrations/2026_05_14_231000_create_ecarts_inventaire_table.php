<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ecarts_inventaire', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fiche_inventaire_id')->constrained('fiches_inventaire')->cascadeOnDelete();
            $table->enum('type_ecart', ['localisation', 'affectation', 'etat', 'manquant', 'excedent', 'code_illisible'])
                ->index();
            $table->text('description')->nullable();
            $table->text('action_corrective')->nullable();
            $table->enum('statut', ['ouvert', 'en_traitement', 'resolu', 'abandonne'])->default('ouvert');
            $table->foreignId('traite_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('traite_le')->nullable();
            $table->timestamps();

            $table->index('statut');
        });

        Schema::create('synchronisations_mobile', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('device_id', 100);
            $table->foreignId('campagne_id')->nullable()->constrained('campagnes_inventaire')->nullOnDelete();
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->enum('direction', ['push', 'pull', 'bidirectionnel'])->default('bidirectionnel');
            $table->unsignedInteger('items_envoyes')->default(0);
            $table->unsignedInteger('items_recus')->default(0);
            $table->json('conflits')->nullable();
            $table->enum('statut', ['en_cours', 'reussi', 'echec', 'partiel'])->default('en_cours');
            $table->timestamps();

            $table->index(['user_id', 'campagne_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('synchronisations_mobile');
        Schema::dropIfExists('ecarts_inventaire');
    }
};
