<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fournisseurs', function (Blueprint $table) {
            $table->id();
            $table->string('raison_sociale', 200);
            $table->string('niu', 30)->nullable()->comment('Numéro d\'identification unique');
            $table->string('contact', 150)->nullable();
            $table->string('adresse', 255)->nullable();
            $table->string('ville', 100)->nullable();
            $table->string('pays', 100)->nullable();
            $table->string('telephone', 30)->nullable();
            $table->string('email')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('raison_sociale');
            $table->index('niu');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fournisseurs');
    }
};
