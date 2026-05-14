<?php

namespace App\Services\Comptabilite;

use App\Models\Immobilisation;
use App\Models\ImportComptable;
use App\Models\LigneComptableImmobilisation;

class MatchingService
{
    /**
     * Tente de matcher automatiquement chaque ligne comptable avec une immobilisation.
     *
     * Stratégie :
     *  1. Match certain : numero_immobilisation_compta exactement identique.
     *  2. Match probable : libellé + valeur (à ±5 %) + date_acquisition (à ±90j).
     *     Score = 100 si tout colle, dégradé proportionnellement.
     *  3. Match impossible : statut = non_trouve.
     */
    public function executer(ImportComptable $import): array
    {
        $stats = ['certain' => 0, 'probable' => 0, 'non_trouve' => 0, 'multiple' => 0];

        $import->lignes()->where('statut_matching', 'non_traite')->each(function ($ligne) use (&$stats) {
            $score = 0;
            $match = null;

            // 1. Match exact par numéro compta
            if (! empty($ligne->numero_immobilisation_compta)) {
                $candidats = Immobilisation::where('numero_immobilisation_compta', $ligne->numero_immobilisation_compta)->get();

                if ($candidats->count() === 1) {
                    $match = $candidats->first();
                    $score = 100;
                    $ligne->update([
                        'immobilisation_id' => $match->id,
                        'statut_matching' => 'matche_auto',
                        'score_matching' => $score,
                    ]);
                    $stats['certain']++;

                    return;
                }

                if ($candidats->count() > 1) {
                    $ligne->update(['statut_matching' => 'multiple']);
                    $stats['multiple']++;

                    return;
                }
            }

            // 2. Match probable par similarité libellé + valeur + date
            $candidats = Immobilisation::query()
                ->when($ligne->libelle, fn ($q) => $q->where('libelle', 'ilike', '%'.substr($ligne->libelle, 0, 30).'%'))
                ->when($ligne->valeur_origine > 0, fn ($q) => $q->whereBetween(
                    'valeur_acquisition',
                    [$ligne->valeur_origine * 0.95, $ligne->valeur_origine * 1.05]
                ))
                ->when($ligne->date_acquisition, fn ($q) => $q->whereBetween(
                    'date_acquisition',
                    [$ligne->date_acquisition->copy()->subDays(90), $ligne->date_acquisition->copy()->addDays(90)]
                ))
                ->limit(2)
                ->get();

            if ($candidats->count() === 1) {
                $score = $this->scoreSimilitude($ligne, $candidats->first());
                if ($score >= 80) {
                    $ligne->update([
                        'immobilisation_id' => $candidats->first()->id,
                        'statut_matching' => 'matche_auto',
                        'score_matching' => $score,
                    ]);
                    $stats['probable']++;

                    return;
                }
            } elseif ($candidats->count() > 1) {
                $ligne->update(['statut_matching' => 'multiple']);
                $stats['multiple']++;

                return;
            }

            $ligne->update(['statut_matching' => 'non_trouve']);
            $stats['non_trouve']++;
        });

        return $stats;
    }

    private function scoreSimilitude(LigneComptableImmobilisation $ligne, Immobilisation $immo): int
    {
        $score = 0;

        if ($ligne->libelle && $immo->libelle) {
            similar_text(strtolower($ligne->libelle), strtolower($immo->libelle), $pct);
            $score += (int) ($pct * 0.5); // max 50
        }

        if ($ligne->valeur_origine > 0 && $immo->valeur_acquisition > 0) {
            $diff = abs($ligne->valeur_origine - $immo->valeur_acquisition) / $immo->valeur_acquisition;
            $score += (int) (max(0, 1 - $diff) * 30); // max 30
        }

        if ($ligne->date_acquisition && $immo->date_acquisition) {
            $diffJours = abs($ligne->date_acquisition->diffInDays($immo->date_acquisition));
            $score += $diffJours <= 30 ? 20 : ($diffJours <= 90 ? 10 : 0); // max 20
        }

        return min(100, $score);
    }
}
