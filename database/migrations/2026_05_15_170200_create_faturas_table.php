<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faturas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('assinatura_id')->constrained('assinaturas')->cascadeOnDelete();
            $table->string('external_id', 120)->nullable();
            $table->string('descricao')->nullable();
            $table->decimal('valor', 10, 2)->default(0);
            $table->date('vencimento')->nullable();
            $table->dateTime('pago_em')->nullable();
            $table->string('status', 20)->default('pendente');
            $table->string('invoice_url')->nullable();
            $table->string('checkout_url')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->unique(['assinatura_id', 'external_id']);
            $table->index(['empresa_id', 'status']);
            $table->index('vencimento');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faturas');
    }
};