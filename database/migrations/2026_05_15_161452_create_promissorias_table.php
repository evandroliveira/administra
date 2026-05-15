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
        Schema::create('promissorias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('conta_id')->unique()->constrained('contas_receber')->cascadeOnDelete();
            $table->unsignedBigInteger('venda_numero')->unique();
            $table->foreign('venda_numero')->references('numero')->on('vendas')->cascadeOnDelete();
            $table->foreignId('cliente_id')->constrained('clientes')->restrictOnDelete();
            $table->decimal('valor_entrada', 12, 2)->default(0);
            $table->decimal('valor_financiado', 12, 2);
            $table->unsignedInteger('quantidade_parcelas')->default(1);
            $table->unsignedInteger('intervalo_dias')->default(30);
            $table->date('primeira_parcela_vencimento');
            $table->decimal('percentual_multa_atraso', 5, 2)->default(2.00);
            $table->decimal('percentual_juros_dia', 6, 4)->default(0.0333);
            $table->string('status', 20)->default('aberta');
            $table->text('observacoes')->nullable();
            $table->dateTime('data_emissao')->useCurrent();
            $table->timestamps();

            $table->index(['empresa_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promissorias');
    }
};
