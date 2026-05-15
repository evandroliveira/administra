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
        Schema::create('pagamentos_receber', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('conta_id')->constrained('contas_receber')->cascadeOnDelete();
            $table->unsignedBigInteger('promissoria_parcela_id')->nullable();
            $table->dateTime('data_pagamento')->useCurrent();
            $table->decimal('valor', 12, 2);
            $table->decimal('valor_abatimento', 12, 2)->default(0);
            $table->decimal('valor_multa', 12, 2)->default(0);
            $table->decimal('valor_juros', 12, 2)->default(0);
            $table->string('metodo', 20)->default('dinheiro');
            $table->text('observacoes')->nullable();
            $table->timestamps();

            $table->index(['empresa_id', 'data_pagamento']);
            $table->index('metodo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pagamentos_receber');
    }
};
