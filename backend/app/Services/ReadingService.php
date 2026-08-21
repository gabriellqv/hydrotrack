<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\Hydrometer;
use App\Models\Reading;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Serviço de ingestão de leituras dos sensores IoT.
 *
 * Processa payloads vindos do endpoint M2M (/api/ingest) e do simulador.
 * Além de persistir a leitura, verifica se o consumo ultrapassou o
 * limiar de alerta e atualiza o status do hidrômetro.
 */
class ReadingService
{
    /** Consumo em m³ acima do qual um alerta de alto consumo é gerado */
    private const HIGH_CONSUMPTION_THRESHOLD = 10.0;

    /**
     * Processa uma leitura vinda de um sensor (ou do simulador).
     *
     * Fluxo:
     * 1. Localiza o hidrômetro pelo código.
     * 2. Persiste a leitura no banco.
     * 3. Atualiza o timestamp e status do hidrômetro.
     * 4. Verifica se o consumo zerou ou ultrapassou o limiar de alerta.
     * 5. Invalida o cache do dashboard.
     *
     * @param  array{hydrometer_code: string, value_m3: float, reading_at: string}  $payload
     * @return Reading A leitura persistida
     *
     * @throws ModelNotFoundException Se o código não existir
     */
    public function ingest(array $payload): Reading
    {
        return DB::transaction(function () use ($payload) {
            $hydrometer = Hydrometer::where('code', $payload['hydrometer_code'])->firstOrFail();

            $reading = Reading::create([
                'hydrometer_id' => $hydrometer->id,
                'value_m3' => $payload['value_m3'],
                'reading_at' => Carbon::parse($payload['reading_at']),
            ]);

            $hydrometer->update([
                'last_reading_at' => $reading->reading_at,
                'status' => 'online',
            ]);

            if ($payload['value_m3'] == 0.0) {
                $this->createZeroReadingAlert($hydrometer);
            } elseif ($payload['value_m3'] > self::HIGH_CONSUMPTION_THRESHOLD) {
                $this->createHighConsumptionAlert($hydrometer, $payload['value_m3']);
            }

            DashboardService::invalidateCache();

            Log::info('Leitura ingerida com sucesso', [
                'hydrometer_id' => $hydrometer->id,
                'reading_id' => $reading->id,
                'value_m3' => $payload['value_m3'],
            ]);

            return $reading;
        });
    }

    /**
     * Gera um alerta de leitura zerada para o hidrômetro.
     *
     * Altera o status do dispositivo para 'alert' e persiste um registro
     * na tabela de alertas para acompanhamento pelo operador.
     *
     * @param  Hydrometer  $hydrometer  Dispositivo que reportou leitura zerada
     */
    private function createZeroReadingAlert(Hydrometer $hydrometer): void
    {
        $hydrometer->update(['status' => 'alert']);

        $alert = Alert::create([
            'hydrometer_id' => $hydrometer->id,
            'type' => 'zero_reading',
            'message' => "Leitura zerada detectada em {$hydrometer->code} ({$hydrometer->neighborhood}). "
                .'Possivel falha no sensor ou medidor travado.',
        ]);

        Log::warning('Alerta de leitura zerada gerado', [
            'hydrometer_id' => $hydrometer->id,
            'alert_id' => $alert->id,
        ]);
    }

    /**
     * Gera um alerta de alto consumo para o hidrômetro.
     *
     * Altera o status do dispositivo para 'alert' e persiste um registro
     * na tabela de alertas para acompanhamento pelo operador.
     *
     * @param  Hydrometer  $hydrometer  Dispositivo que reportou consumo alto
     * @param  float  $value  Valor do consumo em m³
     */
    private function createHighConsumptionAlert(Hydrometer $hydrometer, float $value): void
    {
        $hydrometer->update(['status' => 'alert']);

        $alert = Alert::create([
            'hydrometer_id' => $hydrometer->id,
            'type' => 'high_consumption',
            'message' => "Consumo de {$value} m³ detectado em {$hydrometer->code} "
                ."({$hydrometer->neighborhood}). Limiar: ".self::HIGH_CONSUMPTION_THRESHOLD.' m³.',
        ]);

        Log::warning('Alerta de alto consumo gerado', [
            'hydrometer_id' => $hydrometer->id,
            'alert_id' => $alert->id,
            'value_m3' => $value,
            'threshold_m3' => self::HIGH_CONSUMPTION_THRESHOLD,
        ]);
    }
}
