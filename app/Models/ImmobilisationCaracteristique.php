<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImmobilisationCaracteristique extends Model
{
    protected $table = 'immobilisations_caracteristiques';

    protected $fillable = ['immobilisation_id', 'cle', 'valeur', 'unite'];

    public function immobilisation(): BelongsTo
    {
        return $this->belongsTo(Immobilisation::class);
    }
}
