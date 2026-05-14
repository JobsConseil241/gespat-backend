<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CodificationSequence extends Model
{
    protected $table = 'codification_sequences';

    protected $fillable = ['plan_id', 'cle_groupe', 'valeur'];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(PlanCodification::class, 'plan_id');
    }
}
