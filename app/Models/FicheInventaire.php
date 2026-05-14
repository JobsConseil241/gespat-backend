<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FicheInventaire extends Model
{
    protected $table = 'fiches_inventaire';

    protected $fillable = [
        'campagne_id', 'immobilisation_id', 'code_attendu',
        'localisation_attendue_id', 'service_attendu_id', 'statut',
        'localisation_constatee_id', 'service_constate_id', 'etat_constate',
        'commentaire_inventoriste', 'photos_inventaire', 'gps_lat', 'gps_lng',
        'inventorie_par_user_id', 'inventorie_at',
        'valide_par_user_id', 'valide_at',
        'synchronise_le', 'version',
    ];

    protected function casts(): array
    {
        return [
            'photos_inventaire' => 'array',
            'inventorie_at' => 'datetime',
            'valide_at' => 'datetime',
            'synchronise_le' => 'datetime',
            'gps_lat' => 'decimal:7',
            'gps_lng' => 'decimal:7',
            'version' => 'integer',
        ];
    }

    public function campagne(): BelongsTo { return $this->belongsTo(CampagneInventaire::class, 'campagne_id'); }
    public function immobilisation(): BelongsTo { return $this->belongsTo(Immobilisation::class); }
    public function localisationAttendue(): BelongsTo { return $this->belongsTo(Localisation::class, 'localisation_attendue_id'); }
    public function localisationConstatee(): BelongsTo { return $this->belongsTo(Localisation::class, 'localisation_constatee_id'); }
    public function inventoriePar(): BelongsTo { return $this->belongsTo(User::class, 'inventorie_par_user_id'); }
    public function ecarts(): HasMany { return $this->hasMany(EcartInventaire::class, 'fiche_inventaire_id'); }
}
