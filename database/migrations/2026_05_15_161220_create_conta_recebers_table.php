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
        Schema::create('contas_receber', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->unsignedBigInteger('venda_numero')->unique();
            $table->foreign('venda_numero')->references('numero')->on('vendas')->cascadeOnDelete();
            $table->foreignId('cliente_id')->constrained('clientes')->restrictOnDelete();
            $table->decimal('valor_original', 12, 2);
            $table->decimal('valor_pago', 12, 2)->default(0);
            $table->decimal('valor_juros', 12, 2)->default(0);
            $table->date('data_vencimento');
            $table->dateTime('data_criacao')->useCurrent();
            $table->string('status', 20)->default('aberta');
            $table->text('observacoes')->nullable();
            $table->timestamps();

            $table->index(['empresa_id', 'status']);
            $table->index('data_vencimento');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contas_receber');
    }
};
