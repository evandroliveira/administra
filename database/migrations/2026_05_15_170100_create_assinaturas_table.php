<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assinaturas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('plano_id')->constrained('planos')->restrictOnDelete();
            $table->string('status', 20)->default('ativa');
            $table->string('gateway', 50)->nullable();
            $table->string('gateway_customer_id', 120)->nullable();
            $table->string('gateway_subscription_id', 120)->nullable();
            $table->date('inicio_vigencia');
            $table->date('trial_ends_at')->nullable();
            $table->date('fim_periodo_atual')->nullable();
            $table->boolean('cancelar_no_fim_periodo')->default(false);
            $table->date('ativa_ate')->nullable();
            $table->timestamps();

            $table->index(['empresa_id', 'status']);
            $table->index('gateway_subscription_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assinaturas');
    }
};