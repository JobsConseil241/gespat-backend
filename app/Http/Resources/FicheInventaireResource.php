<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FicheInventaireResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'campagne_id' => $this->campagne_id,
            'immobilisation_id' => $this->immobilisation_id,
            'code_attendu' => $this->code_attendu,
            'localisation_attendue_id' => $this->localisation_attendue_id,
            'service_attendu_id' => $this->service_attendu_id,
            'statut' => $this->statut,
            'localisation_constatee_id' => $this->localisation_constatee_id,
            'service_constate_id' => $this->service_constate_id,
            'etat_constate' => $this->etat_constate,
            'commentaire_inventoriste' => $this->commentaire_inventoriste,
            'photos_inventaire' => $this->photos_inventaire,
            'gps_lat' => $this->gps_lat,
            'gps_lng' => $this->gps_lng,
            'inventorie_par_user_id' => $this->inventorie_par_user_id,
            'inventorie_at' => $this->inventorie_at,
            'valide_par_user_id' => $this->valide_par_user_id,
            'valide_at' => $this->valide_at,
            'synchronise_le' => $this->synchronise_le,
            'version' => $this->version,
            'immobilisation' => new ImmobilisationResource($this->whenLoaded('immobilisation')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
