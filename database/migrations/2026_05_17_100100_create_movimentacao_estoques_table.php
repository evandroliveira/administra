<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimentacao_estoques', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('produto_id')->constrained('produtos')->cascadeOnDelete();
            $table->string('tipo', 40);
            $table->integer('quantidade')->default(0);
            $table->integer('estoque_anterior')->default(0);
            $table->integer('estoque_posterior')->default(0);
            $table->decimal('custo_unitario', 12, 2)->default(0);
            $table->decimal('custo_medio_anterior', 12, 2)->default(0);
            $table->decimal('custo_medio_posterior', 12, 2)->default(0);
            $table->string('origem_tipo', 80)->nullable();
            $table->unsignedBigInteger('origem_id')->nullable();
            $table->text('observacao')->nullable();
            $table->timestamps();

            $table->index(['produto_id', 'created_at']);
            $table->index(['origem_tipo', 'origem_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimentacao_estoques');
    }
};