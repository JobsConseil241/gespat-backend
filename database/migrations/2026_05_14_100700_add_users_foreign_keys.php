<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('service_id')->references('id')->on('services')->nullOnDelete();
            $table->foreign('site_id')->references('id')->on('sites')->nullOnDelete();
        });

        Schema::table('sites', function (Blueprint $table) {
            $table->foreign('responsable_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->dropForeign(['responsable_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['service_id']);
            $table->dropForeign(['site_id']);
        });
    }
};
