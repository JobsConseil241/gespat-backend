<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans_codification', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('libelle', 150);
            $table->text('description')->nullable();
            $table->string('format', 200)->comment('Ex: {ORG}-{CAT}-{SITE}-{ANNEE}-{SEQ:05d}');
            $table->string('separateur', 5)->default('-');
            $table->enum('mode_sequentiel', ['global', 'annuel', 'par_categorie', 'par_site'])->default('annuel');
            $table->boolean('actif')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('codification_sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained('plans_codification')->cascadeOnDelete();
            $table->string('cle_groupe', 100)->comment('Ex: 2026, 2026-MAT-INF, SIEGE-2026');
            $table->unsignedInteger('valeur')->default(0);
            $table->timestamps();

            $table->unique(['plan_id', 'cle_groupe']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('codification_sequences');
        Schema::dropIfExists('plans_codification');
    }
};
