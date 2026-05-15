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
        Schema::create('pagamentos_pagar', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('conta_id')->constrained('contas_pagar')->cascadeOnDelete();
            $table->dateTime('data_pagamento')->useCurrent();
            $table->decimal('valor', 12, 2);
            $table->string('metodo', 20)->default('transferencia');
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
        Schema::dropIfExists('pagamentos_pagar');
    }
};
