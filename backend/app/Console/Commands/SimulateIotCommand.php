<?php

namespace App\Console\Commands;

use App\Models\Hydrometer;
use App\Services\ReadingService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Comando Artisan que simula dispositivos IoT enviando leituras de consumo.
 *
 * Projetado para demonstrações ao vivo: ao rodar este comando em background,
 * o dashboard e o mapa do HydroTrack se atualizam em tempo real, simulando
 * o fluxo de dados de uma rede real de hidrômetros inteligentes.
 *
 * @example php artisan hydrotrack:simulate
 * @example php artisan hydrotrack:simulate --interval=5 --count=20
 */
class SimulateIotCommand extends Command
{
    /**
     * Assinatura do comando no terminal.
     *
     * @var string
     */
    protected $signature = 'hydrotrack:simulate
        {--interval=2 : Intervalo entre leituras em segundos}
        {--count=0 : Número de leituras a gerar (0 = infinito)}';

    /**
     * Descrição exibida no `php artisan list`.
     *
     * @var string
     */
    protected $description = 'Simula sensores IoT enviando leituras de consumo hídrico em tempo real';

    public function __construct(
        private readonly ReadingService $readingService
    ) {
        parent::__construct();
    }

    /**
     * Executa o simulador.
     *
     * Para cada iteração:
     * 1. Seleciona um hidrômetro aleatório com status diferente de 'offline'.
     * 2. Gera um valor de consumo dentro da faixa realista para o tipo do imóvel.
     * 3. Persiste a leitura via ReadingService (que também verifica alertas).
     * 4. Imprime no terminal para acompanhamento em tempo real.
     *
     * @return int Código de saída do comando
     */
    public function handle(): int
    {
        $interval = (int) $this->option('interval');
        $maxCount = (int) $this->option('count');
        $count = 0;

        $this->info("Simulador IoT iniciado (intervalo: {$interval}s)");
        $this->info('   Pressione Ctrl+C para parar.');
        $this->newLine();

        while ($maxCount === 0 || $count < $maxCount) {
            $hydrometer = Hydrometer::where('status', '!=', 'offline')
                ->inRandomOrder()
                ->first();

            if (! $hydrometer) {
                $this->error('Nenhum hidrômetro online encontrado. Rode o seeder primeiro.');

                return self::FAILURE;
            }

            $value = $this->generateRealisticValue($hydrometer->type);

            $this->readingService->ingest([
                'hydrometer_code' => $hydrometer->code,
                'value_m3' => $value,
                'reading_at' => Carbon::now()->toISOString(),
            ]);

            $statusIcon = $value > 10 ? '[ALERTA]' : '[OK]';
            $this->line(
                sprintf(
                    '  %s [%s] %s — %.3f m3 @ %s',
                    $statusIcon,
                    Carbon::now()->format('H:i:s'),
                    $hydrometer->code,
                    $value,
                    $hydrometer->neighborhood
                )
            );

            $count++;
            sleep($interval);
        }

        $this->info("Simulacao finalizada. {$count} leituras geradas.");

        return self::SUCCESS;
    }

    /**
     * Gera um valor de consumo realista baseado no tipo do imóvel.
     *
     * - Residencial: 0.1 a 5.0 m³ (consumo doméstico)
     * - Comercial: 1.0 a 15.0 m³ (lojas, restaurantes)
     * - Industrial: 5.0 a 50.0 m³ (fábricas, lavanderias)
     *
     * @param  string  $type  Tipo do imóvel (residential|commercial|industrial)
     * @return float Consumo em metros cúbicos
     */
    private function generateRealisticValue(string $type): float
    {
        $ranges = [
            'residential' => [0.1, 5.0],
            'commercial' => [1.0, 15.0],
            'industrial' => [5.0, 50.0],
        ];

        [$min, $max] = $ranges[$type] ?? [0.1, 5.0];

        return round($min + mt_rand() / mt_getrandmax() * ($max - $min), 3);
    }
}
