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
        Schema::table('contas_receber', function (Blueprint $table) {
            $table->string('gateway', 20)->nullable();
            $table->string('gateway_payment_id')->nullable();
            $table->text('gateway_invoice_url')->nullable();
            $table->text('gateway_checkout_url')->nullable();
            $table->json('gateway_payload')->nullable();

            $table->index(['empresa_id', 'gateway']);
            $table->index('gateway_payment_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contas_receber', function (Blueprint $table) {
            $table->dropIndex(['empresa_id', 'gateway']);
            $table->dropIndex(['gateway_payment_id']);
            $table->dropColumn([
                'gateway',
                'gateway_payment_id',
                'gateway_invoice_url',
                'gateway_checkout_url',
                'gateway_payload',
            ]);
        });
    }
};