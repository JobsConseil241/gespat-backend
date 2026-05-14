<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories_immobilisations', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('libelle', 150);
            $table->foreignId('parent_id')->nullable()->constrained('categories_immobilisations')->nullOnDelete();
            $table->string('classe_comptable', 10)->nullable();
            $table->string('compte_immobilisation', 20)->nullable();
            $table->string('compte_amortissement', 20)->nullable();
            $table->string('compte_dotation', 20)->nullable();
            $table->unsignedSmallInteger('duree_amortissement_defaut')->nullable()->comment('en mois');
            $table->enum('methode_amortissement_defaut', ['lineaire', 'degressif', 'unite_oeuvre'])->default('lineaire');
            $table->decimal('taux_degressif', 5, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories_immobilisations');
    }
};
