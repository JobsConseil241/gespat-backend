<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LotEtiquettes extends Model
{
    protected $table = 'lots_etiquettes';

    protected $fillable = [
        'libelle', 'nombre_etiquettes', 'format_etiquette',
        'pdf_path', 'genere_par', 'genere_le', 'imprime_par', 'imprime_le',
    ];

    protected function casts(): array
    {
        return [
            'genere_le' => 'datetime',
            'imprime_le' => 'datetime',
        ];
    }

    public function etiquettes(): HasMany
    {
        return $this->hasMany(Etiquette::class, 'lot_id');
    }

    public function genereur(): BelongsTo { return $this->belongsTo(User::class, 'genere_par'); }
    public function imprimeur(): BelongsTo { return $this->belongsTo(User::class, 'imprime_par'); }
}
