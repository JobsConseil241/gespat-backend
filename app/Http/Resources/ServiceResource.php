<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'libelle' => $this->libelle,
            'parent_id' => $this->parent_id,
            'site_principal_id' => $this->site_principal_id,
            'is_active' => $this->is_active,
            'parent' => new ServiceResource($this->whenLoaded('parent')),
            'site_principal' => new SiteResource($this->whenLoaded('sitePrincipal')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
