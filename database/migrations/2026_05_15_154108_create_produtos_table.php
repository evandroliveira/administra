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
        Schema::create('produtos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('codigo', 50);
            $table->string('nome', 200);
            $table->text('descricao')->nullable();
            $table->foreignId('categoria_id')->nullable()->constrained('categorias')->nullOnDelete();
            $table->decimal('preco_custo', 10, 2);
            $table->decimal('preco_venda', 10, 2);
            $table->decimal('margem_lucro', 5, 2)->default(0);
            $table->decimal('custo_medio', 10, 2)->default(0);
            $table->integer('estoque_atual')->default(0);
            $table->integer('estoque_minimo')->default(10);
            $table->boolean('ativo')->default(true);
            $table->string('imagem')->nullable();
            $table->timestamps();

            $table->unique(['empresa_id', 'codigo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('produtos');
    }
};
