<?php

namespace Database\Seeders;

use App\Models\Hydrometer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Popula o banco com hidrômetros realistas distribuídos na mancha urbana de Bocaiúva-MG.
 *
 * Gera 200 hidrômetros com coordenadas GPS contidas em um raio de ~3.5 km
 * do centro da cidade (Lat: -17.1085, Lng: -43.8143), garantindo que todos
 * os pinos do mapa caiam em ruas e bairros reais.
 */
class HydrometerSeeder extends Seeder
{
    /** Centro de Bocaiúva-MG (Praça Wandick Dumont) */
    private const CENTER_LAT = -17.1085;

    private const CENTER_LNG = -43.8143;

    private const RADIUS_KM = 3.5;

    private const NEIGHBORHOODS = [
        'Centro', 'Pernambuco', 'Bonfim', 'Alterosa', 'São José',
        'Santo Antônio', 'Cidade Nova', 'Industrial', 'Recanto das Águas',
        'Jardim Primavera', 'Santa Cruz', 'Planalto',
    ];

    /**
     * Executa o seeder utilizando inserção em lote para performance.
     */
    public function run(): void
    {
        if (Hydrometer::count() > 0) {
            Log::info('HydrometerSeeder: dados já existem, pulando.');

            return;
        }

        DB::transaction(function () {
            $now = Carbon::now();
            $hydrometers = [];

            for ($i = 1; $i <= 200; $i++) {
                [$lat, $lng] = $this->generateCoordinatesInRadius();

                $statuses = ['online', 'online', 'online', 'offline', 'alert'];
                $types = ['residential', 'residential', 'commercial', 'industrial'];

                $hydrometers[] = [
                    'code' => sprintf('HYD-%03d', $i),
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'address' => 'Rua '.Arr::random(self::NEIGHBORHOODS).', '.rand(1, 999),
                    'neighborhood' => Arr::random(self::NEIGHBORHOODS),
                    'status' => Arr::random($statuses),
                    'type' => Arr::random($types),
                    'last_reading_at' => $now->copy()->subMinutes(rand(1, 2880)),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            // Insere hidrômetros em lotes de 50
            foreach (array_chunk($hydrometers, 50) as $chunk) {
                Hydrometer::insert($chunk);
            }

            // Gera leituras em lote para cada hidrômetro
            $allReadings = [];
            $hydrometerIds = Hydrometer::pluck('id');

            foreach ($hydrometerIds as $hydrometer_id) {
                for ($day = 30; $day >= 1; $day--) {
                    $allReadings[] = [
                        'hydrometer_id' => $hydrometer_id,
                        'value_m3' => round(0.1 + (mt_rand() / mt_getrandmax()) * 14.9, 3),
                        'reading_at' => $now->copy()->subDays($day)->setHour(rand(6, 22)),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            // Insere leituras em lotes de 500
            foreach (array_chunk($allReadings, 500) as $chunk) {
                DB::table('readings')->insert($chunk);
            }

            // Gera alertas para hidrômetros com status 'alert'
            $alertHydrometers = Hydrometer::where('status', 'alert')->pluck('id');
            $alerts = [];
            $types = ['high_consumption', 'zero_reading', 'offline'];
            $messages = [
                'high_consumption' => 'Consumo acima de 150% da média detectado nas últimas 24h.',
                'zero_reading' => 'Nenhuma leitura registrada nas últimas 48 horas.',
                'offline' => 'Dispositivo sem comunicação há mais de 72 horas.',
            ];

            foreach ($alertHydrometers as $hydrometer_id) {
                $type = Arr::random($types);
                $resolved = rand(1, 100) <= 30; // 30% já resolvidos

                $alerts[] = [
                    'hydrometer_id' => $hydrometer_id,
                    'type' => $type,
                    'message' => $messages[$type],
                    'resolved' => $resolved,
                    'resolved_at' => $resolved ? $now->copy()->subHours(rand(1, 48)) : null,
                    'created_at' => $now->copy()->subHours(rand(1, 168)),
                    'updated_at' => $now,
                ];
            }

            foreach (array_chunk($alerts, 50) as $chunk) {
                DB::table('alerts')->insert($chunk);
            }
        });
    }

    /**
     * Gera coordenadas aleatórias dentro do raio definido ao redor do centro de Bocaiúva.
     *
     * Utiliza distribuição polar uniforme para evitar concentração de pontos no centro.
     *
     * @return array{float, float} [latitude, longitude]
     */
    private function generateCoordinatesInRadius(): array
    {
        $radiusInDegrees = self::RADIUS_KM / 111.32;

        $u = mt_rand() / mt_getrandmax();
        $v = mt_rand() / mt_getrandmax();

        $w = $radiusInDegrees * sqrt($u);
        $t = 2 * M_PI * $v;

        $lat = self::CENTER_LAT + ($w * cos($t));
        $lng = self::CENTER_LNG + ($w * sin($t) / cos(deg2rad(self::CENTER_LAT)));

        return [round($lat, 7), round($lng, 7)];
    }
}
