<?php

namespace App\Services\Codification;

use App\Models\CategorieImmobilisation;
use App\Models\CodificationSequence;
use App\Models\PlanCodification;
use App\Models\Site;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CodificationService
{
    /**
     * Génère le prochain code inventaire selon le plan actif.
     *
     * Tokens supportés dans le format :
     *   {ORG}    : préfixe organisme (config gespat.org_code)
     *   {CAT}    : code catégorie
     *   {SITE}   : code site
     *   {ANNEE}  : année courante (4 chiffres)
     *   {MOIS}   : mois courant (2 chiffres)
     *   {SEQ:Nd} : séquentiel sur N chiffres, ex {SEQ:05d} -> 00125
     */
    public function genererCode(CategorieImmobilisation $categorie, Site $site, ?PlanCodification $plan = null): string
    {
        $plan = $plan ?? PlanCodification::actif();
        if (! $plan) {
            throw new RuntimeException('Aucun plan de codification actif. Activez-en un dans /api/v1/codification/plans.');
        }

        return DB::transaction(function () use ($plan, $categorie, $site) {
            $cleGroupe = $this->buildCleGroupe($plan, $categorie, $site);

            $seq = CodificationSequence::lockForUpdate()
                ->firstOrCreate(
                    ['plan_id' => $plan->id, 'cle_groupe' => $cleGroupe],
                    ['valeur' => 0]
                );

            $seq->increment('valeur');

            return $this->renderFormat($plan, $categorie, $site, $seq->valeur);
        });
    }

    public function previsualiser(CategorieImmobilisation $categorie, Site $site, ?PlanCodification $plan = null): string
    {
        $plan = $plan ?? PlanCodification::actif();
        if (! $plan) {
            return '[Aucun plan actif]';
        }

        $cleGroupe = $this->buildCleGroupe($plan, $categorie, $site);
        $seq = CodificationSequence::where('plan_id', $plan->id)
            ->where('cle_groupe', $cleGroupe)
            ->value('valeur') ?? 0;

        return $this->renderFormat($plan, $categorie, $site, $seq + 1);
    }

    private function buildCleGroupe(PlanCodification $plan, CategorieImmobilisation $categorie, Site $site): string
    {
        return match ($plan->mode_sequentiel) {
            'annuel' => (string) now()->year,
            'par_categorie' => now()->year.'-'.$categorie->code,
            'par_site' => $site->code.'-'.now()->year,
            default => 'global',
        };
    }

    private function renderFormat(PlanCodification $plan, CategorieImmobilisation $categorie, Site $site, int $valeur): string
    {
        $tokens = [
            '{ORG}' => config('gespat.org_code', 'XYZ'),
            '{CAT}' => $categorie->code,
            '{SITE}' => $site->code,
            '{ANNEE}' => (string) now()->year,
            '{MOIS}' => now()->format('m'),
        ];

        $code = strtr($plan->format, $tokens);

        // {SEQ:05d} -> séquence formatée
        $code = preg_replace_callback('/\{SEQ:(\d+)d\}/', function ($m) use ($valeur) {
            return str_pad((string) $valeur, (int) $m[1], '0', STR_PAD_LEFT);
        }, $code);

        // {SEQ} sans formatage
        $code = str_replace('{SEQ}', (string) $valeur, $code);

        return $code;
    }
}
