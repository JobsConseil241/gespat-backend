<?php

namespace App\Http\Requests\Immobilisations;

use Illuminate\Foundation\Http\FormRequest;

class UpdateImmobilisationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('immobilisations.update') ?? false;
    }

    public function rules(): array
    {
        // Code inventaire immuable une fois généré — interdire la modification.
        return [
            'libelle' => ['sometimes', 'string', 'max:200'],
            'description' => ['nullable', 'string'],

            'categorie_id' => ['sometimes', 'integer', 'exists:categories_immobilisations,id'],
            'marque_id' => ['nullable', 'integer', 'exists:marques,id'],
            'modele_id' => ['nullable', 'integer', 'exists:modeles,id'],
            'numero_serie' => ['nullable', 'string', 'max:100'],
            'numero_chassis' => ['nullable', 'string', 'max:100'],
            'numero_immatriculation' => ['nullable', 'string', 'max:30'],

            'site_id' => ['sometimes', 'integer', 'exists:sites,id'],
            'localisation_id' => ['nullable', 'integer', 'exists:localisations,id'],
            'service_id' => ['nullable', 'integer', 'exists:services,id'],
            'affecte_a_user_id' => ['nullable', 'integer', 'exists:users,id'],

            'date_acquisition' => ['nullable', 'date'],
            'mode_acquisition' => ['nullable', 'in:achat,donation,transfert,production_interne'],
            'fournisseur_id' => ['nullable', 'integer', 'exists:fournisseurs,id'],
            'numero_facture' => ['nullable', 'string', 'max:100'],

            'valeur_acquisition' => ['nullable', 'numeric', 'min:0'],
            'valeur_acquisition_ht' => ['nullable', 'numeric', 'min:0'],
            'tva' => ['nullable', 'numeric', 'min:0'],

            'duree_amortissement_mois' => ['nullable', 'integer', 'min:1', 'max:600'],
            'methode_amortissement' => ['nullable', 'in:lineaire,degressif,unite_oeuvre,non_amortissable'],
            'date_mise_en_service' => ['nullable', 'date'],
            'valeur_residuelle' => ['nullable', 'numeric', 'min:0'],

            'etat_physique' => ['nullable', 'in:neuf,bon,moyen,mauvais,hors_service'],
            'statut' => ['nullable', 'in:actif,en_maintenance,en_transfert,reforme,cede,perdu,vole,detruit'],
        ];
    }
}
