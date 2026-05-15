<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planos', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 100)->unique();
            $table->text('descricao')->nullable();
            $table->decimal('valor_mensal', 10, 2)->default(0);
            $table->unsignedInteger('limite_usuarios')->default(5);
            $table->unsignedInteger('limite_produtos')->default(1000);
            $table->boolean('permite_promissoria')->default(true);
            $table->boolean('permite_relatorios_pdf')->default(true);
            $table->boolean('permite_exportacao_xlsx')->default(true);
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planos');
    }
};