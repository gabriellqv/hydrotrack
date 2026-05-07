<?php

namespace App\Models;

use Database\Factories\HydrometerFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Representa um hidrômetro (sensor de medição de consumo hídrico).
 *
 * Cada hidrômetro está instalado em uma localização GPS específica e
 * recebe leituras periódicas de consumo. O campo `status` é atualizado
 * automaticamente pelo job de watchdog quando o dispositivo para de reportar.
 *
 * @property string $code Código único do hidrômetro
 * @property float $latitude Coordenada GPS
 * @property float $longitude Coordenada GPS
 * @property string $status online|offline|alert
 */
class Hydrometer extends Model
{
    /** @use HasFactory<HydrometerFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
        'latitude',
        'longitude',
        'address',
        'neighborhood',
        'status',
        'type',
        'last_reading_at',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'last_reading_at' => 'datetime',
    ];

    /**
     * Retorna todas as leituras de consumo associadas a este hidrômetro.
     *
     * @return HasMany<Reading>
     */
    public function readings(): HasMany
    {
        return $this->hasMany(Reading::class);
    }

    /**
     * Retorna todos os alertas gerados para este hidrômetro.
     *
     * @return HasMany<Alert>
     */
    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class);
    }

    /**
     * Scope para filtrar hidrômetros por bairro.
     *
     * @param  Builder  $query
     * @param  string  $neighborhood  Nome do bairro
     * @return Builder
     */
    public function scopeByNeighborhood($query, string $neighborhood)
    {
        return $query->where('neighborhood', $neighborhood);
    }

    /**
     * Scope para filtrar hidrômetros por status.
     *
     * @param  Builder  $query
     * @param  string  $status  online|offline|alert
     * @return Builder
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }
}
