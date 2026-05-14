<?php

namespace App\Services\Amortissement;

use App\Models\AmortissementAnnuel;
use App\Models\Immobilisation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AmortissementService
{
    /**
     * Calcule (ou recalcule) les amortissements annuels d'une immobilisation
     * pour tous les exercices, du début de mise en service jusqu'à $exerciceMax.
     *
     * Algorithme :
     *  - Linéaire : dotation = valeur_amortissable / durée_annees, prorata 1er exercice.
     *  - Dégressif : dotation = VNC_début × taux_dégressif, bascule en linéaire dès
     *    que la dotation linéaire sur durée restante devient supérieure.
     *
     * Valeur amortissable = valeur_acquisition - valeur_residuelle.
     */
    public function calculerImmobilisation(Immobilisation $immo, int $exerciceMax): array
    {
        if (! $immo->date_mise_en_service || ! $immo->duree_amortissement_mois || $immo->methode_amortissement === 'non_amortissable') {
            return [];
        }

        $valeurOrigine = (float) $immo->valeur_acquisition;
        $valeurResiduelle = (float) $immo->valeur_residuelle;
        $valeurAmortissable = max(0, $valeurOrigine - $valeurResiduelle);
        $dureeMois = (int) $immo->duree_amortissement_mois;
        $dureeAnnees = $dureeMois / 12;
        $mes = Carbon::parse($immo->date_mise_en_service);
        $exerciceDebut = $mes->year;

        $methode = $immo->methode_amortissement;
        $tauxDegressif = $methode === 'degressif'
            ? $this->coefficientDegressif($dureeAnnees) / $dureeAnnees
            : null;

        $cumulAmort = 0.0;
        $vnc = $valeurOrigine;
        $resultats = [];

        for ($exercice = $exerciceDebut; $exercice <= $exerciceMax; $exercice++) {
            $vncDebut = $vnc;
            $cumulDebut = $cumulAmort;
            $valeurBruteDebut = $valeurOrigine;

            // Calcul du prorata pour le 1er exercice
            $moisActifs = 12;
            if ($exercice === $exerciceDebut) {
                $moisActifs = 13 - $mes->month;
            }

            $dotation = $this->calculerDotation(
                $methode, $valeurAmortissable, $dureeMois, $dureeAnnees,
                $tauxDegressif, $vncDebut, $valeurResiduelle, $moisActifs,
                $exercice - $exerciceDebut
            );

            // Plafonner pour ne pas amortir en dessous de la valeur résiduelle
            $dotation = min($dotation, max(0, $vncDebut - $valeurResiduelle));
            $dotation = round($dotation, 2);

            $cumulAmort = round($cumulDebut + $dotation, 2);
            $vnc = round($valeurOrigine - $cumulAmort, 2);

            $resultats[] = [
                'exercice' => $exercice,
                'valeur_brute_debut' => $valeurBruteDebut,
                'cumul_amortissement_debut' => $cumulDebut,
                'vnc_debut' => $vncDebut,
                'dotation_exercice' => $dotation,
                'cumul_amortissement_fin' => $cumulAmort,
                'vnc_fin' => $vnc,
                'methode_utilisee' => $methode,
            ];

            if ($vnc <= $valeurResiduelle + 0.01) {
                break;
            }
        }

        return $resultats;
    }

    /**
     * Calcule la dotation pour un exercice donné selon la méthode.
     */
    private function calculerDotation(
        string $methode,
        float $valeurAmortissable,
        int $dureeMois,
        float $dureeAnnees,
        ?float $tauxDegressif,
        float $vncDebut,
        float $valeurResiduelle,
        int $moisActifs,
        int $rangAnnee
    ): float {
        $dotationLineaire = ($valeurAmortissable / $dureeMois) * $moisActifs;

        return match ($methode) {
            'lineaire' => $dotationLineaire,
            'degressif' => $this->dotationDegressive(
                $tauxDegressif, $vncDebut, $valeurResiduelle,
                $dureeMois, $rangAnnee, $moisActifs, $dotationLineaire
            ),
            default => $dotationLineaire, // unite_oeuvre nécessite une donnée externe
        };
    }

    private function dotationDegressive(
        float $tauxAnnuel,
        float $vncDebut,
        float $valeurResiduelle,
        int $dureeMoisTotale,
        int $rangAnnee,
        int $moisActifs,
        float $dotationLineaireRef
    ): float {
        $dotationDeg = $vncDebut * $tauxAnnuel * ($moisActifs / 12);

        // Bascule vers linéaire si la dotation linéaire sur la durée restante est supérieure
        $moisRestants = max(1, $dureeMoisTotale - ($rangAnnee * 12));
        $dotationLineaireRestante = (($vncDebut - $valeurResiduelle) / $moisRestants) * $moisActifs;

        return max($dotationDeg, $dotationLineaireRestante);
    }

    /**
     * Coefficient dégressif SYSCOHADA selon durée.
     */
    private function coefficientDegressif(float $dureeAnnees): float
    {
        return match (true) {
            $dureeAnnees <= 4 => 1.5,
            $dureeAnnees <= 6 => 2.0,
            default => 2.5,
        };
    }

    /**
     * Persiste (ou met à jour) les amortissements calculés en BDD pour
     * l'immobilisation. Retourne le nombre de lignes écrites.
     */
    public function persister(Immobilisation $immo, int $exerciceMax): int
    {
        return DB::transaction(function () use ($immo, $exerciceMax) {
            $resultats = $this->calculerImmobilisation($immo, $exerciceMax);
            $count = 0;

            foreach ($resultats as $r) {
                AmortissementAnnuel::updateOrCreate(
                    [
                        'immobilisation_id' => $immo->id,
                        'exercice' => $r['exercice'],
                        'mois' => null,
                    ],
                    array_merge($r, ['est_valide' => false])
                );
                $count++;
            }

            return $count;
        });
    }
}
