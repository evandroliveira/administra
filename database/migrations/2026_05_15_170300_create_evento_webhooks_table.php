<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evento_webhooks', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 30);
            $table->string('event_type', 80);
            $table->string('external_id', 160)->nullable();
            $table->foreignId('empresa_id')->nullable()->constrained('empresas')->nullOnDelete();
            $table->foreignId('assinatura_id')->nullable()->constrained('assinaturas')->nullOnDelete();
            $table->foreignId('fatura_id')->nullable()->constrained('faturas')->nullOnDelete();
            $table->json('payload')->nullable();
            $table->string('status', 20)->default('recebido');
            $table->text('erro')->nullable();
            $table->timestamp('processado_em')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'event_type', 'external_id']);
            $table->index(['provider', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evento_webhooks');
    }
};