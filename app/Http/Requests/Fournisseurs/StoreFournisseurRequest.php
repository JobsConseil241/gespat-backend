<?php

namespace App\Http\Requests\Fournisseurs;

use Illuminate\Foundation\Http\FormRequest;

class StoreFournisseurRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('fournisseurs.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'raison_sociale' => ['required', 'string', 'max:200'],
            'niu' => ['nullable', 'string', 'max:30'],
            'contact' => ['nullable', 'string', 'max:150'],
            'adresse' => ['nullable', 'string', 'max:255'],
            'ville' => ['nullable', 'string', 'max:100'],
            'pays' => ['nullable', 'string', 'max:100'],
            'telephone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'is_active' => ['boolean'],
        ];
    }
}
