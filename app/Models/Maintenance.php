<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Contracts\Auditable;

class Maintenance extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'immobilisation_id', 'type', 'date_prevue', 'date_realisation',
        'description_intervention', 'cout', 'prestataire', 'kilometrage',
        'prochaine_echeance', 'statut', 'cree_par_user_id',
    ];

    protected function casts(): array
    {
        return [
            'date_prevue' => 'date',
            'date_realisation' => 'date',
            'prochaine_echeance' => 'date',
            'cout' => 'decimal:2',
        ];
    }

    public function immobilisation(): BelongsTo { return $this->belongsTo(Immobilisation::class); }
}
