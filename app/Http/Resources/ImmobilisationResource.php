<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ImmobilisationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code_inventaire' => $this->code_inventaire,
            'qr_payload' => $this->qr_payload,
            'libelle' => $this->libelle,
            'description' => $this->description,

            'categorie_id' => $this->categorie_id,
            'categorie' => new CategorieImmobilisationResource($this->whenLoaded('categorie')),
            'marque_id' => $this->marque_id,
            'modele_id' => $this->modele_id,
            'numero_serie' => $this->numero_serie,
            'numero_chassis' => $this->numero_chassis,
            'numero_immatriculation' => $this->numero_immatriculation,

            'site_id' => $this->site_id,
            'site' => new SiteResource($this->whenLoaded('site')),
            'localisation_id' => $this->localisation_id,
            'localisation' => new LocalisationResource($this->whenLoaded('localisation')),
            'service_id' => $this->service_id,
            'service' => new ServiceResource($this->whenLoaded('service')),
            'affecte_a_user_id' => $this->affecte_a_user_id,

            'date_acquisition' => $this->date_acquisition?->format('Y-m-d'),
            'mode_acquisition' => $this->mode_acquisition,
            'fournisseur_id' => $this->fournisseur_id,
            'fournisseur' => new FournisseurResource($this->whenLoaded('fournisseur')),
            'numero_facture' => $this->numero_facture,

            'valeur_acquisition' => $this->valeur_acquisition,
            'valeur_acquisition_ht' => $this->valeur_acquisition_ht,
            'tva' => $this->tva,
            'devise' => $this->devise,

            'est_immobilise_comptablement' => $this->est_immobilise_comptablement,
            'compte_immobilisation' => $this->compte_immobilisation,
            'numero_immobilisation_compta' => $this->numero_immobilisation_compta,

            'duree_amortissement_mois' => $this->duree_amortissement_mois,
            'methode_amortissement' => $this->methode_amortissement,
            'date_mise_en_service' => $this->date_mise_en_service?->format('Y-m-d'),
            'valeur_residuelle' => $this->valeur_residuelle,

            'etat_physique' => $this->etat_physique,
            'statut' => $this->statut,
            'photo_principale_path' => $this->photo_principale_path,

            'photos' => $this->whenLoaded('photos'),
            'documents' => $this->whenLoaded('documents'),
            'caracteristiques' => $this->whenLoaded('caracteristiques'),
            'bien_immobilier' => $this->whenLoaded('bienImmobilier'),

            'created_by' => $this->created_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
