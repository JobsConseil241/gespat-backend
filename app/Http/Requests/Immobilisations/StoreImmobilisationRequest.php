<?php

namespace App\Http\Requests\Immobilisations;

use Illuminate\Foundation\Http\FormRequest;

class StoreImmobilisationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('immobilisations.create') ?? false;
    }

    /**
     * Aligne les nullables sur les defaults SQL avant validation : les colonnes
     * NOT NULL avec default(0) doivent recevoir 0, pas null, sinon la contrainte
     * PostgreSQL est violée.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'valeur_acquisition' => $this->valeur_acquisition ?? 0,
            'valeur_residuelle' => $this->valeur_residuelle ?? 0,
            'devise' => $this->devise ?: 'XAF',
        ]);
    }

    public function rules(): array
    {
        return [
            // Code optionnel — généré automatiquement si absent
            'code_inventaire' => ['nullable', 'string', 'max:100', 'unique:immobilisations,code_inventaire'],
            'libelle' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],

            'categorie_id' => ['required', 'integer', 'exists:categories_immobilisations,id'],
            'marque_id' => ['nullable', 'integer', 'exists:marques,id'],
            'modele_id' => ['nullable', 'integer', 'exists:modeles,id'],
            'numero_serie' => ['nullable', 'string', 'max:100'],
            'numero_chassis' => ['nullable', 'string', 'max:100'],
            'numero_immatriculation' => ['nullable', 'string', 'max:30'],

            'site_id' => ['required', 'integer', 'exists:sites,id'],
            'localisation_id' => ['nullable', 'integer', 'exists:localisations,id'],
            'service_id' => ['nullable', 'integer', 'exists:services,id'],
            'affecte_a_user_id' => ['nullable', 'integer', 'exists:users,id'],

            'date_acquisition' => ['nullable', 'date'],
            'mode_acquisition' => ['nullable', 'in:achat,donation,transfert,production_interne'],
            'fournisseur_id' => ['nullable', 'integer', 'exists:fournisseurs,id'],
            'numero_facture' => ['nullable', 'string', 'max:100'],
            'numero_bon_commande' => ['nullable', 'string', 'max:100'],
            'numero_bon_livraison' => ['nullable', 'string', 'max:100'],

            'valeur_acquisition' => ['nullable', 'numeric', 'min:0'],
            'valeur_acquisition_ht' => ['nullable', 'numeric', 'min:0'],
            'tva' => ['nullable', 'numeric', 'min:0'],
            'devise' => ['nullable', 'string', 'size:3'],

            'est_immobilise_comptablement' => ['boolean'],
            'compte_immobilisation' => ['nullable', 'string', 'max:20'],
            'numero_immobilisation_compta' => ['nullable', 'string', 'max:50'],

            'duree_amortissement_mois' => ['nullable', 'integer', 'min:1', 'max:600'],
            'methode_amortissement' => ['nullable', 'in:lineaire,degressif,unite_oeuvre,non_amortissable'],
            'date_mise_en_service' => ['nullable', 'date'],
            'valeur_residuelle' => ['nullable', 'numeric', 'min:0'],

            'etat_physique' => ['nullable', 'in:neuf,bon,moyen,mauvais,hors_service'],
            'statut' => ['nullable', 'in:actif,en_maintenance,en_transfert,reforme,cede,perdu,vole,detruit'],
        ];
    }
}
