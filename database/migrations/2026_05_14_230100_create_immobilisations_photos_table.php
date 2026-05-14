<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('immobilisations_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('immobilisation_id')->constrained('immobilisations')->cascadeOnDelete();
            $table->string('path', 500);
            $table->string('legende', 255)->nullable();
            $table->unsignedSmallInteger('ordre')->default(0);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('immobilisations_photos');
    }
};
