<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportComptable extends Model
{
    protected $table = 'imports_comptables';

    protected $fillable = [
        'source', 'fichier_source_path', 'exercice', 'date_import',
        'importe_par', 'statut',
        'nombre_lignes', 'nombre_lignes_valides', 'nombre_lignes_rejetees',
    ];

    protected function casts(): array
    {
        return ['date_import' => 'datetime'];
    }

    public function lignes(): HasMany
    {
        return $this->hasMany(LigneComptableImmobilisation::class, 'import_id');
    }
}
