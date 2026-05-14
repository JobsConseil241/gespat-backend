<?php

namespace App\Http\Requests\Sites;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSiteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('sites.update') ?? false;
    }

    public function rules(): array
    {
        $siteId = $this->route('site')?->id;

        return [
            'code' => ['sometimes', 'string', 'max:30', Rule::unique('sites', 'code')->ignore($siteId)],
            'libelle' => ['sometimes', 'string', 'max:150'],
            'type' => ['sometimes', 'in:siege,agence,depot,site_distant'],
            'adresse' => ['nullable', 'string', 'max:255'],
            'ville' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'responsable_id' => ['nullable', 'integer', 'exists:users,id'],
            'is_active' => ['boolean'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }
}
