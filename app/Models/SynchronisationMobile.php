<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SynchronisationMobile extends Model
{
    protected $table = 'synchronisations_mobile';

    protected $fillable = [
        'user_id', 'device_id', 'campagne_id',
        'started_at', 'completed_at', 'direction',
        'items_envoyes', 'items_recus', 'conflits', 'statut',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'conflits' => 'array',
        ];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function campagne(): BelongsTo { return $this->belongsTo(CampagneInventaire::class, 'campagne_id'); }
}
