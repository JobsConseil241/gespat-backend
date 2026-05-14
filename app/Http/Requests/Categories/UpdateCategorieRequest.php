<?php

namespace App\Http\Requests\Categories;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategorieRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('categories.update') ?? false;
    }

    public function rules(): array
    {
        $categorieId = $this->route('categorie')?->id;

        return [
            'code' => ['sometimes', 'string', 'max:30', Rule::unique('categories_immobilisations', 'code')->ignore($categorieId)],
            'libelle' => ['sometimes', 'string', 'max:150'],
            'parent_id' => ['nullable', 'integer', 'exists:categories_immobilisations,id', "different:{$categorieId}"],
            'classe_comptable' => ['nullable', 'string', 'max:10'],
            'compte_immobilisation' => ['nullable', 'string', 'max:20'],
            'compte_amortissement' => ['nullable', 'string', 'max:20'],
            'compte_dotation' => ['nullable', 'string', 'max:20'],
            'duree_amortissement_defaut' => ['nullable', 'integer', 'min:1', 'max:600'],
            'methode_amortissement_defaut' => ['sometimes', 'in:lineaire,degressif,unite_oeuvre'],
            'taux_degressif' => ['nullable', 'numeric', 'between:0,100'],
            'is_active' => ['boolean'],
        ];
    }
}
