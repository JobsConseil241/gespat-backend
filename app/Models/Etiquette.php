<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Etiquette extends Model
{
    protected $fillable = [
        'lot_id', 'immobilisation_id', 'code_inventaire',
        'qr_payload', 'barcode_payload', 'etat', 'remplace_etiquette_id',
    ];

    public function lot(): BelongsTo { return $this->belongsTo(LotEtiquettes::class, 'lot_id'); }
    public function immobilisation(): BelongsTo { return $this->belongsTo(Immobilisation::class); }
    public function remplace(): BelongsTo { return $this->belongsTo(self::class, 'remplace_etiquette_id'); }
}
