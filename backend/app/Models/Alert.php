<?php

namespace App\Models;

use Database\Factories\AlertFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Representa um alerta gerado pelo sistema de monitoramento.
 *
 * Alertas são criados automaticamente pelo watchdog (dispositivo offline)
 * ou pela lógica de ingestão (consumo anormal, leitura zerada).
 *
 * @property string $type high_consumption|zero_reading|offline
 * @property bool $resolved Indica se o alerta foi tratado por um operador
 */
class Alert extends Model
{
    /** @use HasFactory<AlertFactory> */
    use HasFactory;

    protected $fillable = [
        'hydrometer_id',
        'type',
        'message',
        'resolved',
        'resolved_at',
    ];

    protected $casts = [
        'resolved' => 'boolean',
        'resolved_at' => 'datetime',
    ];

    /**
     * Retorna o hidrômetro associado a este alerta.
     *
     * @return BelongsTo<Hydrometer, Alert>
     */
    public function hydrometer(): BelongsTo
    {
        return $this->belongsTo(Hydrometer::class);
    }
}
