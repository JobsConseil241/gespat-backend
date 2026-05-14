<?php

namespace App\Http\Requests\Localisations;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLocalisationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('localisations.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'site_id' => ['required', 'integer', 'exists:sites,id'],
            'parent_id' => ['nullable', 'integer', 'exists:localisations,id'],
            'code' => [
                'required', 'string', 'max:50',
                Rule::unique('localisations', 'code')->where(fn ($q) => $q->where('site_id', $this->site_id)),
            ],
            'libelle' => ['required', 'string', 'max:150'],
            'type' => ['required', 'in:batiment,etage,salle,bureau,depot,exterieur'],
        ];
    }
}
