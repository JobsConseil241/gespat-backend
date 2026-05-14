<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('immobilisation_id')->constrained('immobilisations')->cascadeOnDelete();
            $table->enum('type', ['preventive', 'curative', 'controle_technique']);
            $table->date('date_prevue')->nullable();
            $table->date('date_realisation')->nullable();
            $table->text('description_intervention')->nullable();
            $table->decimal('cout', 15, 2)->nullable();
            $table->string('prestataire', 200)->nullable();
            $table->unsignedInteger('kilometrage')->nullable();
            $table->date('prochaine_echeance')->nullable();
            $table->enum('statut', ['planifiee', 'en_cours', 'terminee', 'annulee'])->default('planifiee');
            $table->foreignId('cree_par_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['immobilisation_id', 'statut']);
            $table->index('prochaine_echeance');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenances');
    }
};
