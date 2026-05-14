<?php

namespace App\Imports;

use App\Models\ImportComptable;
use App\Models\LigneComptableImmobilisation;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class EcrituresComptablesImport implements ToModel, WithHeadingRow
{
    public function __construct(private readonly ImportComptable $import) {}

    /**
     * Headers attendus (insensibles à la casse, normalisés via Maatwebsite) :
     *   numero, libelle, compte, date_acquisition, valeur_origine,
     *   cumul_amortissement, vnc, service, localisation
     */
    public function model(array $row)
    {
        $valeur = (float) ($row['valeur_origine'] ?? $row['valeur'] ?? 0);
        if ($valeur <= 0 && empty($row['libelle'])) {
            return null; // ligne vide
        }

        $this->import->increment('nombre_lignes');

        return new LigneComptableImmobilisation([
            'import_id' => $this->import->id,
            'numero_immobilisation_compta' => $row['numero'] ?? $row['numero_immobilisation'] ?? null,
            'libelle' => $row['libelle'] ?? null,
            'compte' => $row['compte'] ?? null,
            'date_acquisition' => isset($row['date_acquisition'])
                ? \Carbon\Carbon::parse($row['date_acquisition'])
                : null,
            'valeur_origine' => $valeur,
            'cumul_amortissement' => (float) ($row['cumul_amortissement'] ?? 0),
            'vnc' => (float) ($row['vnc'] ?? max(0, $valeur - (float) ($row['cumul_amortissement'] ?? 0))),
            'service_compta' => $row['service'] ?? null,
            'localisation_compta' => $row['localisation'] ?? null,
            'statut_matching' => 'non_traite',
        ]);
    }
}
