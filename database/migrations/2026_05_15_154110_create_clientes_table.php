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
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('tipo', 2)->default('PF');
            $table->string('nome', 200);
            $table->string('email');
            $table->string('telefone', 20);
            $table->string('celular', 20)->nullable();
            $table->string('cpf_cnpj', 20);
            $table->string('rg_ie', 20)->nullable();
            $table->text('endereco');
            $table->string('numero', 10);
            $table->string('complemento', 100)->nullable();
            $table->string('bairro', 100);
            $table->string('cidade', 100);
            $table->string('estado', 2);
            $table->string('cep', 10);
            $table->decimal('limite_credito', 10, 2)->default(0);
            $table->decimal('credito_disponivel', 10, 2)->default(0);
            $table->decimal('percentual_multa_atraso_padrao', 5, 2)->default(2.00);
            $table->decimal('percentual_juros_dia_padrao', 6, 4)->default(0.0333);
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->unique(['empresa_id', 'email']);
            $table->unique(['empresa_id', 'cpf_cnpj']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
