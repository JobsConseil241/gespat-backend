<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('localisations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained('sites')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('localisations')->nullOnDelete();
            $table->string('code', 50);
            $table->string('libelle', 150);
            $table->enum('type', ['batiment', 'etage', 'salle', 'bureau', 'depot', 'exterieur'])->default('salle');
            $table->unsignedSmallInteger('niveau')->default(0);
            $table->string('path', 500)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['site_id', 'code']);
            $table->index('parent_id');
            $table->index('path');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('localisations');
    }
};
