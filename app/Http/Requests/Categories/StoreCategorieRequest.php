<?php

namespace App\Http\Requests\Categories;

use Illuminate\Foundation\Http\FormRequest;

class StoreCategorieRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('categories.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:30', 'unique:categories_immobilisations,code'],
            'libelle' => ['required', 'string', 'max:150'],
            'parent_id' => ['nullable', 'integer', 'exists:categories_immobilisations,id'],
            'classe_comptable' => ['nullable', 'string', 'max:10'],
            'compte_immobilisation' => ['nullable', 'string', 'max:20'],
            'compte_amortissement' => ['nullable', 'string', 'max:20'],
            'compte_dotation' => ['nullable', 'string', 'max:20'],
            'duree_amortissement_defaut' => ['nullable', 'integer', 'min:1', 'max:600'],
            'methode_amortissement_defaut' => ['required', 'in:lineaire,degressif,unite_oeuvre'],
            'taux_degressif' => ['nullable', 'numeric', 'between:0,100'],
            'is_active' => ['boolean'],
        ];
    }
}
