<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AmortissementAnnuel extends Model
{
    protected $table = 'amortissements_annuels';

    protected $fillable = [
        'immobilisation_id', 'exercice', 'mois',
        'valeur_brute_debut', 'cumul_amortissement_debut', 'vnc_debut',
        'dotation_exercice', 'cumul_amortissement_fin', 'vnc_fin',
        'methode_utilisee', 'est_valide', 'valide_par', 'valide_le',
    ];

    protected function casts(): array
    {
        return [
            'valeur_brute_debut' => 'decimal:2',
            'cumul_amortissement_debut' => 'decimal:2',
            'vnc_debut' => 'decimal:2',
            'dotation_exercice' => 'decimal:2',
            'cumul_amortissement_fin' => 'decimal:2',
            'vnc_fin' => 'decimal:2',
            'est_valide' => 'boolean',
            'valide_le' => 'datetime',
        ];
    }

    public function immobilisation(): BelongsTo { return $this->belongsTo(Immobilisation::class); }
}
