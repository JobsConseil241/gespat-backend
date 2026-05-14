<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class PlanCodification extends Model implements Auditable
{
    use SoftDeletes;
    use \OwenIt\Auditing\Auditable;

    protected $table = 'plans_codification';

    protected $fillable = [
        'code', 'libelle', 'description', 'format', 'separateur', 'mode_sequentiel', 'actif',
    ];

    protected function casts(): array
    {
        return ['actif' => 'boolean'];
    }

    public function sequences(): HasMany
    {
        return $this->hasMany(CodificationSequence::class, 'plan_id');
    }

    public static function actif(): ?self
    {
        return static::where('actif', true)->first();
    }
}
