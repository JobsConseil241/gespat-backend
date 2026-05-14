<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EcartInventaire extends Model
{
    protected $table = 'ecarts_inventaire';

    protected $fillable = [
        'fiche_inventaire_id', 'type_ecart', 'description', 'action_corrective',
        'statut', 'traite_par', 'traite_le',
    ];

    protected function casts(): array
    {
        return ['traite_le' => 'datetime'];
    }

    public function fiche(): BelongsTo { return $this->belongsTo(FicheInventaire::class, 'fiche_inventaire_id'); }
    public function traitePar(): BelongsTo { return $this->belongsTo(User::class, 'traite_par'); }
}
