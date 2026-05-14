<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Contracts\Auditable;

class BienImmobilier extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;

    protected $table = 'biens_immobiliers';

    protected $fillable = [
        'immobilisation_id', 'superficie_terrain_m2', 'superficie_batie_m2',
        'nombre_niveaux', 'annee_construction', 'titre_foncier', 'numero_cadastre', 'usage',
    ];

    protected function casts(): array
    {
        return [
            'superficie_terrain_m2' => 'decimal:2',
            'superficie_batie_m2' => 'decimal:2',
            'nombre_niveaux' => 'integer',
            'annee_construction' => 'integer',
        ];
    }

    public function immobilisation(): BelongsTo
    {
        return $this->belongsTo(Immobilisation::class);
    }
}
