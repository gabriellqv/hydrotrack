<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cria a tabela de hidrômetros para rastreamento de dispositivos de telemetria.
     *
     * Cada hidrômetro possui coordenadas GPS para exibição no mapa interativo
     * e um status que é atualizado automaticamente pelo job de watchdog.
     */
    public function up(): void
    {
        Schema::create('hydrometers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique()->comment('Código único do hidrômetro (ex: HYD-001)');
            $table->decimal('latitude', 10, 7)->comment('Coordenada GPS - latitude');
            $table->decimal('longitude', 10, 7)->comment('Coordenada GPS - longitude');
            $table->string('address')->comment('Endereço legível do ponto de instalação');
            $table->string('neighborhood', 100)->comment('Bairro - usado para filtros e agrupamento');
            $table->enum('status', ['online', 'offline', 'alert'])->default('online');
            $table->enum('type', ['residential', 'commercial', 'industrial'])->default('residential');
            $table->timestamp('last_reading_at')->nullable()->comment('Última leitura recebida do sensor');
            $table->timestamps();

            $table->index('neighborhood');
            $table->index('status');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hydrometers');
    }
};
