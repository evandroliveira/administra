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
        Schema::create('vendas', function (Blueprint $table) {
            $table->bigIncrements('numero');
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('cliente_id')->constrained('clientes')->restrictOnDelete();
            $table->unsignedBigInteger('vendedor_id')->nullable();
            $table->dateTime('data_venda')->useCurrent();
            $table->date('data_entrega')->nullable();
            $table->string('status', 20)->default('pendente');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('desconto', 12, 2)->default(0);
            $table->decimal('frete', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->decimal('percentual_multa_atraso_promissoria', 5, 2)->default(2.00);
            $table->decimal('percentual_juros_dia_promissoria', 6, 4)->default(0.0333);
            $table->boolean('emitir_nota_fiscal')->default(false);
            $table->string('status_nota_fiscal', 20)->default('nao_emitir');
            $table->string('nota_fiscal_numero', 50)->nullable();
            $table->string('nota_fiscal_serie', 20)->nullable();
            $table->string('nota_fiscal_chave', 80)->nullable();
            $table->string('nota_fiscal_protocolo', 120)->nullable();
            $table->string('nota_fiscal_url_pdf')->nullable();
            $table->string('nota_fiscal_url_xml')->nullable();
            $table->text('nota_fiscal_mensagem')->nullable();
            $table->json('nota_fiscal_payload')->nullable();
            $table->dateTime('nota_fiscal_emitida_em')->nullable();
            $table->decimal('lucro_total', 12, 2)->default(0);
            $table->text('observacoes')->nullable();
            $table->text('cancelamento_motivo')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('data_venda');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendas');
    }
};
