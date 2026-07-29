<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cria a tabela de leituras de consumo hídrico.
     *
     * Cada registro representa uma leitura enviada por um hidrômetro em um
     * momento específico. O campo value_m3 armazena o consumo em metros cúbicos
     * com precisão de 3 casas decimais.
     */
    public function up(): void
    {
        Schema::create('readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hydrometer_id')->constrained()->cascadeOnDelete();
            $table->decimal('value_m3', 10, 3)->comment('Consumo em metros cúbicos');
            $table->timestamp('reading_at')->comment('Momento exato da leitura pelo sensor');
            $table->timestamps();

            $table->index(['hydrometer_id', 'reading_at']);
            $table->index('reading_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('readings');
    }
};
