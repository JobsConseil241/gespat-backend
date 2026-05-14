<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EtatReconciliation extends Model
{
    protected $table = 'etats_reconciliation';

    protected $fillable = [
        'exercice', 'date_arrete',
        'valeur_brute_compta', 'valeur_brute_patrimoine', 'ecart',
        'nombre_lignes_compta', 'nombre_immo_patrimoine',
        'genere_par', 'genere_le', 'pdf_path',
    ];

    protected function casts(): array
    {
        return [
            'date_arrete' => 'date',
            'genere_le' => 'datetime',
            'valeur_brute_compta' => 'decimal:2',
            'valeur_brute_patrimoine' => 'decimal:2',
            'ecart' => 'decimal:2',
        ];
    }
}
