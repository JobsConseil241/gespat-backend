<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lots_etiquettes', function (Blueprint $table) {
            $table->id();
            $table->string('libelle', 200);
            $table->unsignedInteger('nombre_etiquettes');
            $table->enum('format_etiquette', ['62x29', '38x90', 'A4_avery_24', 'A4_avery_30'])->default('A4_avery_24');
            $table->string('pdf_path', 500)->nullable();
            $table->foreignId('genere_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('genere_le')->nullable();
            $table->foreignId('imprime_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('imprime_le')->nullable();
            $table->timestamps();
        });

        Schema::create('etiquettes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lot_id')->nullable()->constrained('lots_etiquettes')->nullOnDelete();
            $table->foreignId('immobilisation_id')->nullable()->constrained('immobilisations')->nullOnDelete();
            $table->string('code_inventaire', 100);
            $table->string('qr_payload', 500)->nullable();
            $table->string('barcode_payload', 100)->nullable();
            $table->enum('etat', ['genere', 'imprime', 'applique', 'perdu', 'remplace'])->default('genere');
            $table->foreignId('remplace_etiquette_id')->nullable()->constrained('etiquettes')->nullOnDelete();
            $table->timestamps();

            $table->index('code_inventaire');
            $table->index('etat');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('etiquettes');
        Schema::dropIfExists('lots_etiquettes');
    }
};
