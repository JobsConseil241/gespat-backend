<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImmobilisationPhoto extends Model
{
    protected $table = 'immobilisations_photos';

    protected $fillable = ['immobilisation_id', 'path', 'legende', 'ordre', 'uploaded_by'];

    public function immobilisation(): BelongsTo
    {
        return $this->belongsTo(Immobilisation::class);
    }
}
