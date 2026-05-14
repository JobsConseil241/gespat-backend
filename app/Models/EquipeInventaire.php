<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class EquipeInventaire extends Model
{
    protected $table = 'equipes_inventaire';

    protected $fillable = ['campagne_id', 'libelle', 'chef_equipe_id', 'perimetre_assigne'];

    protected function casts(): array
    {
        return ['perimetre_assigne' => 'array'];
    }

    public function campagne(): BelongsTo { return $this->belongsTo(CampagneInventaire::class, 'campagne_id'); }
    public function chef(): BelongsTo { return $this->belongsTo(User::class, 'chef_equipe_id'); }

    public function membres(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'membres_equipe_inventaire', 'equipe_id', 'user_id')
            ->withPivot('role_equipe')
            ->withTimestamps();
    }
}
