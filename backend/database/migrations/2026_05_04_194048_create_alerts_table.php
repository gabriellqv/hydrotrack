<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cria a tabela de alertas do sistema de monitoramento.
     *
     * Alertas são gerados automaticamente pelo job de watchdog ou pela
     * lógica de ingestão quando padrões anormais são detectados.
     */
    public function up(): void
    {
        Schema::create('alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hydrometer_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['high_consumption', 'zero_reading', 'offline']);
            $table->text('message');
            $table->boolean('resolved')->default(false);
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['resolved', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }
};
