<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategorieImmobilisationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'libelle' => $this->libelle,
            'parent_id' => $this->parent_id,
            'classe_comptable' => $this->classe_comptable,
            'compte_immobilisation' => $this->compte_immobilisation,
            'compte_amortissement' => $this->compte_amortissement,
            'compte_dotation' => $this->compte_dotation,
            'duree_amortissement_defaut' => $this->duree_amortissement_defaut,
            'methode_amortissement_defaut' => $this->methode_amortissement_defaut,
            'taux_degressif' => $this->taux_degressif,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
