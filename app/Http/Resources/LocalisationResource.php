<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LocalisationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'site_id' => $this->site_id,
            'parent_id' => $this->parent_id,
            'code' => $this->code,
            'libelle' => $this->libelle,
            'type' => $this->type,
            'niveau' => $this->niveau,
            'path' => $this->path,
            'site' => new SiteResource($this->whenLoaded('site')),
            'parent' => new LocalisationResource($this->whenLoaded('parent')),
            'enfants' => LocalisationResource::collection($this->whenLoaded('enfants')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
