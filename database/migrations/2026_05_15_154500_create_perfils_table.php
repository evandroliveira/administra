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
        Schema::create('perfis', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 50)->unique();
            $table->text('descricao')->nullable();
            $table->boolean('pode_vender')->default(false);
            $table->boolean('pode_gerar_relatorios')->default(false);
            $table->boolean('pode_gerenciar_usuarios')->default(false);
            $table->boolean('pode_gerenciar_financeiro')->default(false);
            $table->boolean('pode_editar_produtos')->default(false);
            $table->boolean('pode_editar_clientes')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('perfis');
    }
};
