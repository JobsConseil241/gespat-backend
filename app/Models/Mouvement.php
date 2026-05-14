<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Contracts\Auditable;

class Mouvement extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'immobilisation_id', 'type_mouvement', 'date_mouvement', 'motif',
        'site_origine_id', 'localisation_origine_id', 'service_origine_id',
        'site_destination_id', 'localisation_destination_id', 'service_destination_id',
        'document_path', 'statut', 'valide_par_user_id', 'valide_at',
        'cree_par_user_id',
    ];

    protected function casts(): array
    {
        return [
            'date_mouvement' => 'date',
            'valide_at' => 'datetime',
        ];
    }

    public function immobilisation(): BelongsTo { return $this->belongsTo(Immobilisation::class); }
    public function siteOrigine(): BelongsTo { return $this->belongsTo(Site::class, 'site_origine_id'); }
    public function siteDestination(): BelongsTo { return $this->belongsTo(Site::class, 'site_destination_id'); }
    public function valideur(): BelongsTo { return $this->belongsTo(User::class, 'valide_par_user_id'); }
}
