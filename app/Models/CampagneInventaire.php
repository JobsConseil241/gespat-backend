<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class CampagneInventaire extends Model implements Auditable
{
    use HasFactory, SoftDeletes;
    use \OwenIt\Auditing\Auditable;

    protected $table = 'campagnes_inventaire';

    protected $fillable = [
        'code', 'libelle', 'description', 'type',
        'date_debut_prevue', 'date_fin_prevue',
        'date_debut_reelle', 'date_fin_reelle',
        'perimetre', 'statut', 'responsable_id',
        'cloturee_par', 'cloturee_le',
    ];

    protected function casts(): array
    {
        return [
            'date_debut_prevue' => 'date',
            'date_fin_prevue' => 'date',
            'date_debut_reelle' => 'datetime',
            'date_fin_reelle' => 'datetime',
            'cloturee_le' => 'datetime',
            'perimetre' => 'array',
        ];
    }

    public function responsable(): BelongsTo { return $this->belongsTo(User::class, 'responsable_id'); }
    public function equipes(): HasMany { return $this->hasMany(EquipeInventaire::class, 'campagne_id'); }
    public function fiches(): HasMany { return $this->hasMany(FicheInventaire::class, 'campagne_id'); }
}
