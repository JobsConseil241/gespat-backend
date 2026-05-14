<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CampagneInventaireResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'libelle' => $this->libelle,
            'description' => $this->description,
            'type' => $this->type,
            'date_debut_prevue' => $this->date_debut_prevue?->format('Y-m-d'),
            'date_fin_prevue' => $this->date_fin_prevue?->format('Y-m-d'),
            'date_debut_reelle' => $this->date_debut_reelle,
            'date_fin_reelle' => $this->date_fin_reelle,
            'perimetre' => $this->perimetre,
            'statut' => $this->statut,
            'responsable_id' => $this->responsable_id,
            'fiches_count' => $this->whenCounted('fiches'),
            'fiches_inventorees' => $this->when(
                isset($this->fiches_inventorees),
                fn () => $this->fiches_inventorees
            ),
            'taux_avancement' => $this->when(
                isset($this->taux_avancement),
                fn () => $this->taux_avancement
            ),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
