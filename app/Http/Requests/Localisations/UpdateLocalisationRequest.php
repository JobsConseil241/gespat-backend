<?php

namespace App\Http\Requests\Localisations;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLocalisationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('localisations.update') ?? false;
    }

    public function rules(): array
    {
        $loc = $this->route('localisation');

        return [
            'parent_id' => ['nullable', 'integer', 'exists:localisations,id', "different:{$loc?->id}"],
            'code' => [
                'sometimes', 'string', 'max:50',
                Rule::unique('localisations', 'code')
                    ->where(fn ($q) => $q->where('site_id', $loc?->site_id))
                    ->ignore($loc?->id),
            ],
            'libelle' => ['sometimes', 'string', 'max:150'],
            'type' => ['sometimes', 'in:batiment,etage,salle,bureau,depot,exterieur'],
        ];
    }
}
