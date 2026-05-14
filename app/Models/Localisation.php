<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class Localisation extends Model implements Auditable
{
    use HasFactory, SoftDeletes;
    use \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'site_id',
        'parent_id',
        'code',
        'libelle',
        'type',
        'niveau',
        'path',
    ];

    protected static function booted(): void
    {
        static::saving(function (Localisation $loc) {
            $loc->niveau = $loc->parent_id ? (optional($loc->parent)->niveau ?? 0) + 1 : 0;
            $parentPath = $loc->parent?->path ?? optional($loc->site)->code;
            $loc->path = trim(($parentPath ? $parentPath.'/' : '').$loc->code, '/');
        });
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function enfants(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }
}
