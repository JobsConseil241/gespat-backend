<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SiteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'libelle' => $this->libelle,
            'type' => $this->type,
            'adresse' => $this->adresse,
            'ville' => $this->ville,
            'province' => $this->province,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'responsable_id' => $this->responsable_id,
            'responsable' => new UserResource($this->whenLoaded('responsable')),
            'is_active' => $this->is_active,
            'localisations_count' => $this->whenCounted('localisations'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
