<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('immobilisations_caracteristiques', function (Blueprint $table) {
            $table->id();
            $table->foreignId('immobilisation_id')->constrained('immobilisations')->cascadeOnDelete();
            $table->string('cle', 100);
            $table->string('valeur', 500);
            $table->string('unite', 30)->nullable();
            $table->timestamps();

            $table->unique(['immobilisation_id', 'cle']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('immobilisations_caracteristiques');
    }
};
