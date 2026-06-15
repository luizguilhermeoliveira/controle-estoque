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
        Schema::create('movimentacoes', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo', ['entrada', 'saida', 'transferencia']);
            $table->foreignId('material_id')->constrained('materiais')->cascadeOnDelete();
            $table->unsignedInteger('quantidade');
            $table->foreignId('almoxarifado_origem_id')->nullable()->constrained('almoxarifados')->nullOnDelete();
            $table->foreignId('almoxarifado_destino_id')->nullable()->constrained('almoxarifados')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movimentacoes');
    }
};
