<?php

namespace App\Http\Requests\Services;

use Illuminate\Foundation\Http\FormRequest;

class StoreServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('services.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:30', 'unique:services,code'],
            'libelle' => ['required', 'string', 'max:150'],
            'parent_id' => ['nullable', 'integer', 'exists:services,id'],
            'site_principal_id' => ['nullable', 'integer', 'exists:sites,id'],
            'is_active' => ['boolean'],
        ];
    }
}
