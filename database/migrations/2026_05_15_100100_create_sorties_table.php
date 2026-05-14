<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sorties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('immobilisation_id')->constrained('immobilisations')->cascadeOnDelete();
            $table->enum('type_sortie', ['reforme', 'cession', 'perte', 'vol', 'destruction']);
            $table->date('date_decision');
            $table->string('numero_decision', 100)->nullable();
            $table->text('motif');
            $table->decimal('prix_cession', 15, 2)->nullable();
            $table->string('acquereur_nom', 200)->nullable();
            $table->string('acquereur_contact', 150)->nullable();
            $table->string('pv_path', 500)->nullable();
            $table->json('commission_membres')->nullable();
            $table->foreignId('propose_par')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('valide_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('valide_le')->nullable();
            $table->enum('statut', ['proposee', 'validee', 'executee', 'refusee'])->default('proposee');
            $table->timestamps();

            $table->index('statut');
            $table->index('type_sortie');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sorties');
    }
};
