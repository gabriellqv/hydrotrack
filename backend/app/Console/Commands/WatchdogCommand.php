<?php

namespace App\Console\Commands;

use App\Models\Alert;
use App\Models\Hydrometer;
use App\Services\DashboardService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Job de vigilância que detecta hidrômetros sem comunicação.
 *
 * Em sistemas de telemetria reais, o maior problema não é quando o sensor
 * envia dados errados — é quando ele PARA de enviar. Bateria acabou,
 * antena quebrou, firmware travou. Este comando detecta esse cenário.
 *
 * Agendado para rodar a cada 5 minutos via Laravel Scheduler.
 *
 * @example php artisan hydrotrack:watchdog
 * @example php artisan hydrotrack:watchdog --threshold=12
 */
class WatchdogCommand extends Command
{
    /**
     * Assinatura do comando no terminal.
     *
     * @var string
     */
    protected $signature = 'hydrotrack:watchdog
        {--threshold=24 : Horas sem comunicação para considerar offline}';

    /**
     * Descrição exibida no `php artisan list`.
     *
     * @var string
     */
    protected $description = 'Detecta hidrômetros sem comunicação e marca como offline';

    /**
     * Executa a varredura de dispositivos silenciosos.
     *
     * Busca todos os hidrômetros que não estão offline e cuja última
     * leitura foi há mais de N horas (threshold). Para cada um encontrado,
     * atualiza o status para 'offline' e gera um alerta no sistema.
     *
     * @return int Código de saída
     */
    public function handle(): int
    {
        $thresholdHours = (int) $this->option('threshold');
        $cutoff = Carbon::now()->subHours($thresholdHours);

        $staleHydrometers = Hydrometer::where('status', '!=', 'offline')
            ->where(function ($query) use ($cutoff) {
                $query->where('last_reading_at', '<', $cutoff)
                    ->orWhereNull('last_reading_at');
            })
            ->get();

        if ($staleHydrometers->isEmpty()) {
            $this->info('Todos os hidrômetros estão comunicando normalmente.');

            return self::SUCCESS;
        }

        $count = 0;

        // Bulk update: 1 query em vez de N queries individuais
        $ids = $staleHydrometers->pluck('id');
        Hydrometer::whereIn('id', $ids)->update(['status' => 'offline']);

        // Batch insert: 1 query em vez de N queries individuais
        $alertsData = $staleHydrometers->map(fn ($h) => [
            'hydrometer_id' => $h->id,
            'type' => 'offline',
            'message' => "Hidrômetro {$h->code} ({$h->neighborhood}) "
                ."não envia dados há mais de {$thresholdHours} horas.",
            'resolved' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ])->toArray();

        Alert::insert($alertsData);
        $count = $ids->count();

        DashboardService::invalidateCache();

        $this->warn("{$count} hidrômetro(s) marcado(s) como offline.");

        return self::SUCCESS;
    }
}
