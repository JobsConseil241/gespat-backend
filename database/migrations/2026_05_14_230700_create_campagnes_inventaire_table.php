<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campagnes_inventaire', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('libelle', 200);
            $table->text('description')->nullable();
            $table->enum('type', ['general_annuel', 'partiel', 'tournant', 'contradictoire'])->default('partiel');
            $table->date('date_debut_prevue');
            $table->date('date_fin_prevue');
            $table->timestamp('date_debut_reelle')->nullable();
            $table->timestamp('date_fin_reelle')->nullable();
            $table->json('perimetre')->comment('{sites:[], localisations:[], categories:[]}');
            $table->enum('statut', ['preparation', 'en_cours', 'cloturee', 'annulee'])->default('preparation');
            $table->foreignId('responsable_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('cloturee_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cloturee_le')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('statut');
            $table->index('date_debut_prevue');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campagnes_inventaire');
    }
};
