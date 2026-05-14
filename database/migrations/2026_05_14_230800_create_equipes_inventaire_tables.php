<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipes_inventaire', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campagne_id')->constrained('campagnes_inventaire')->cascadeOnDelete();
            $table->string('libelle', 150);
            $table->foreignId('chef_equipe_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('perimetre_assigne')->nullable()->comment('{sites:[], localisations:[]}');
            $table->timestamps();
        });

        Schema::create('membres_equipe_inventaire', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipe_id')->constrained('equipes_inventaire')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('role_equipe', ['chef', 'membre', 'observateur'])->default('membre');
            $table->timestamps();

            $table->unique(['equipe_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('membres_equipe_inventaire');
        Schema::dropIfExists('equipes_inventaire');
    }
};
