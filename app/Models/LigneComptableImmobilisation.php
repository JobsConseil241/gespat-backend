<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LigneComptableImmobilisation extends Model
{
    protected $table = 'lignes_comptables_immobilisations';

    protected $fillable = [
        'import_id', 'numero_immobilisation_compta', 'libelle', 'compte',
        'date_acquisition', 'valeur_origine', 'cumul_amortissement', 'vnc',
        'service_compta', 'localisation_compta',
        'immobilisation_id', 'statut_matching', 'score_matching',
    ];

    protected function casts(): array
    {
        return [
            'date_acquisition' => 'date',
            'valeur_origine' => 'decimal:2',
            'cumul_amortissement' => 'decimal:2',
            'vnc' => 'decimal:2',
        ];
    }

    public function import(): BelongsTo { return $this->belongsTo(ImportComptable::class, 'import_id'); }
    public function immobilisation(): BelongsTo { return $this->belongsTo(Immobilisation::class); }
}
