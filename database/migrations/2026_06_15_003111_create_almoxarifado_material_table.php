<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('almoxarifado_material', function (Blueprint $table) {
            $table->id();
            $table->foreignId('almoxarifado_id')->constrained('almoxarifados')->cascadeOnDelete();
            $table->foreignId('material_id')->constrained('materiais')->cascadeOnDelete();
            $table->unsignedInteger('quantidade')->default(0);
            $table->timestamps();

            $table->unique(['almoxarifado_id', 'material_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('almoxarifado_material');
    }
};
