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
        Schema::table('clientes', function (Blueprint $table) {
            $table->string('gateway', 20)->nullable();
            $table->string('gateway_customer_id')->nullable();

            $table->index(['empresa_id', 'gateway']);
            $table->index('gateway_customer_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropIndex(['empresa_id', 'gateway']);
            $table->dropIndex(['gateway_customer_id']);
            $table->dropColumn(['gateway', 'gateway_customer_id']);
        });
    }
};