<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fiches_inventaire', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campagne_id')->constrained('campagnes_inventaire')->cascadeOnDelete();
            $table->foreignId('immobilisation_id')->nullable()->constrained('immobilisations')->nullOnDelete();
            $table->string('code_attendu', 100)->nullable();
            $table->foreignId('localisation_attendue_id')->nullable()->constrained('localisations')->nullOnDelete();
            $table->foreignId('service_attendu_id')->nullable()->constrained('services')->nullOnDelete();
            $table->enum('statut', ['a_inventorier', 'vu_conforme', 'vu_ecart', 'non_trouve', 'decouverte', 'litige'])
                ->default('a_inventorier');
            $table->foreignId('localisation_constatee_id')->nullable()->constrained('localisations')->nullOnDelete();
            $table->foreignId('service_constate_id')->nullable()->constrained('services')->nullOnDelete();
            $table->enum('etat_constate', ['neuf', 'bon', 'moyen', 'mauvais', 'hors_service'])->nullable();
            $table->text('commentaire_inventoriste')->nullable();
            $table->json('photos_inventaire')->nullable();
            $table->decimal('gps_lat', 10, 7)->nullable();
            $table->decimal('gps_lng', 10, 7)->nullable();
            $table->foreignId('inventorie_par_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('inventorie_at')->nullable();
            $table->foreignId('valide_par_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('valide_at')->nullable();
            $table->timestamp('synchronise_le')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();

            $table->index(['campagne_id', 'statut']);
            $table->index('immobilisation_id');
            $table->index('inventorie_par_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fiches_inventaire');
    }
};
