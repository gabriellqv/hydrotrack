<?php

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\ReadingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Representa uma leitura de consumo hídrico enviada por um hidrômetro.
 *
 * @property float $value_m3 Consumo em metros cúbicos
 * @property Carbon $reading_at Momento da leitura
 */
class Reading extends Model
{
    /** @use HasFactory<ReadingFactory> */
    use HasFactory;

    protected $fillable = [
        'hydrometer_id',
        'value_m3',
        'reading_at',
    ];

    protected $casts = [
        'value_m3' => 'decimal:3',
        'reading_at' => 'datetime',
    ];

    /**
     * Retorna o hidrômetro que gerou esta leitura.
     *
     * @return BelongsTo<Hydrometer, Reading>
     */
    public function hydrometer(): BelongsTo
    {
        return $this->belongsTo(Hydrometer::class);
    }
}
