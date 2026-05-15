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
        Schema::table('pagamentos_receber', function (Blueprint $table) {
            $table->foreign('promissoria_parcela_id')
                ->references('id')
                ->on('promissoria_parcelas')
                ->nullOnDelete();

            $table->index('promissoria_parcela_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pagamentos_receber', function (Blueprint $table) {
            $table->dropForeign(['promissoria_parcela_id']);
            $table->dropIndex(['promissoria_parcela_id']);
        });
    }
};
