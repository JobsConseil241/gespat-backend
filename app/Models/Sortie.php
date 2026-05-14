<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Contracts\Auditable;

class Sortie extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'immobilisation_id', 'type_sortie', 'date_decision', 'numero_decision',
        'motif', 'prix_cession', 'acquereur_nom', 'acquereur_contact',
        'pv_path', 'commission_membres',
        'propose_par', 'valide_par', 'valide_le', 'statut',
    ];

    protected function casts(): array
    {
        return [
            'date_decision' => 'date',
            'valide_le' => 'datetime',
            'commission_membres' => 'array',
            'prix_cession' => 'decimal:2',
        ];
    }

    public function immobilisation(): BelongsTo { return $this->belongsTo(Immobilisation::class); }
    public function valideur(): BelongsTo { return $this->belongsTo(User::class, 'valide_par'); }
}
