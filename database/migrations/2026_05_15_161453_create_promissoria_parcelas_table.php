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
        Schema::create('promissoria_parcelas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('promissoria_id')->constrained('promissorias')->cascadeOnDelete();
            $table->unsignedInteger('numero');
            $table->decimal('valor_original', 12, 2);
            $table->decimal('valor_pago', 12, 2)->default(0);
            $table->decimal('valor_abatimento', 12, 2)->default(0);
            $table->date('data_vencimento');
            $table->string('status', 20)->default('aberta');
            $table->text('observacoes')->nullable();
            $table->timestamps();

            $table->unique(['promissoria_id', 'numero']);
            $table->index(['empresa_id', 'status']);
            $table->index('data_vencimento');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promissoria_parcelas');
    }
};
