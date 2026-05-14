<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImmobilisationDocument extends Model
{
    protected $table = 'immobilisations_documents';

    protected $fillable = ['immobilisation_id', 'type', 'path', 'libelle', 'taille_octets', 'uploaded_by'];

    public function immobilisation(): BelongsTo
    {
        return $this->belongsTo(Immobilisation::class);
    }
}
