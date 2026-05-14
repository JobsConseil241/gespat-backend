<?php

namespace App\Exports;

use App\Models\Immobilisation;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ImmobilisationsExport implements FromQuery, WithHeadings, WithMapping
{
    public function __construct(
        private readonly ?int $siteId = null,
        private readonly ?int $categorieId = null,
        private readonly ?string $statut = null,
    ) {}

    public function query()
    {
        return Immobilisation::query()
            ->with(['categorie:id,code,libelle', 'site:id,code,libelle', 'service:id,code,libelle'])
            ->when($this->siteId, fn ($q) => $q->where('site_id', $this->siteId))
            ->when($this->categorieId, fn ($q) => $q->where('categorie_id', $this->categorieId))
            ->when($this->statut, fn ($q) => $q->where('statut', $this->statut))
            ->orderBy('code_inventaire');
    }

    public function headings(): array
    {
        return [
            'Code inventaire', 'Libellé', 'Catégorie', 'Site', 'Service',
            'N° série', 'Date acquisition', 'Valeur acquisition', 'Devise',
            'Durée amort. (mois)', 'Méthode amort.', 'Date mise en service',
            'État physique', 'Statut',
        ];
    }

    public function map($immo): array
    {
        return [
            $immo->code_inventaire,
            $immo->libelle,
            $immo->categorie?->code,
            $immo->site?->code,
            $immo->service?->code,
            $immo->numero_serie,
            $immo->date_acquisition?->format('Y-m-d'),
            $immo->valeur_acquisition,
            $immo->devise,
            $immo->duree_amortissement_mois,
            $immo->methode_amortissement,
            $immo->date_mise_en_service?->format('Y-m-d'),
            $immo->etat_physique,
            $immo->statut,
        ];
    }
}
