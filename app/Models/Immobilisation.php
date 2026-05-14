<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class Immobilisation extends Model implements Auditable
{
    use HasFactory, SoftDeletes;
    use \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'code_inventaire', 'qr_payload', 'libelle', 'description',
        'categorie_id', 'marque_id', 'modele_id',
        'numero_serie', 'numero_chassis', 'numero_immatriculation',
        'site_id', 'localisation_id', 'service_id', 'affecte_a_user_id',
        'date_acquisition', 'mode_acquisition', 'fournisseur_id',
        'numero_facture', 'numero_bon_commande', 'numero_bon_livraison',
        'valeur_acquisition', 'valeur_acquisition_ht', 'tva', 'devise', 'taux_change',
        'est_immobilise_comptablement', 'compte_immobilisation', 'numero_immobilisation_compta',
        'duree_amortissement_mois', 'methode_amortissement', 'date_mise_en_service', 'valeur_residuelle',
        'etat_physique', 'statut',
        'photo_principale_path',
        'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'date_acquisition' => 'date',
            'date_mise_en_service' => 'date',
            'est_immobilise_comptablement' => 'boolean',
            'valeur_acquisition' => 'decimal:2',
            'valeur_acquisition_ht' => 'decimal:2',
            'tva' => 'decimal:2',
            'taux_change' => 'decimal:6',
            'valeur_residuelle' => 'decimal:2',
            'duree_amortissement_mois' => 'integer',
            'dernier_inventaire_date' => 'datetime',
        ];
    }

    public function categorie(): BelongsTo
    {
        return $this->belongsTo(CategorieImmobilisation::class, 'categorie_id');
    }

    public function marque(): BelongsTo { return $this->belongsTo(Marque::class); }

    public function modele(): BelongsTo { return $this->belongsTo(Modele::class); }

    public function site(): BelongsTo { return $this->belongsTo(Site::class); }

    public function localisation(): BelongsTo { return $this->belongsTo(Localisation::class); }

    public function service(): BelongsTo { return $this->belongsTo(Service::class); }

    public function fournisseur(): BelongsTo { return $this->belongsTo(Fournisseur::class); }

    public function affecteA(): BelongsTo { return $this->belongsTo(User::class, 'affecte_a_user_id'); }

    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }

    public function photos(): HasMany
    {
        return $this->hasMany(ImmobilisationPhoto::class)->orderBy('ordre');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ImmobilisationDocument::class)->latest();
    }

    public function caracteristiques(): HasMany
    {
        return $this->hasMany(ImmobilisationCaracteristique::class);
    }

    public function bienImmobilier(): HasOne
    {
        return $this->hasOne(BienImmobilier::class);
    }
}
