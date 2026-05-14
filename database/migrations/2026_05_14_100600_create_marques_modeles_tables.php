<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marques', function (Blueprint $table) {
            $table->id();
            $table->string('libelle', 100)->unique();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('modeles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marque_id')->constrained('marques')->cascadeOnDelete();
            $table->string('libelle', 150);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['marque_id', 'libelle']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modeles');
        Schema::dropIfExists('marques');
    }
};
