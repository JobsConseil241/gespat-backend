<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AmortissementAnnuel;
use App\Models\CampagneInventaire;
use App\Models\Immobilisation;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:reports.view'),
        ];
    }

    public function kpi(): JsonResponse
    {
        $stats = DB::table('immobilisations')
            ->whereNull('deleted_at')
            ->selectRaw('
                count(*) as total_biens,
                sum(case when statut = ? then 1 else 0 end) as biens_actifs,
                sum(case when statut in (?, ?, ?, ?, ?) then 1 else 0 end) as biens_sortis,
                sum(valeur_acquisition) as valeur_totale_acquisition
            ', ['actif', 'reforme', 'cede', 'perdu', 'vole', 'detruit'])
            ->first();

        $campagnesEnCours = CampagneInventaire::where('statut', 'en_cours')->count();

        $vncTotale = AmortissementAnnuel::where('exercice', now()->year)->sum('vnc_fin');

        return response()->json([
            'data' => [
                'biens' => [
                    'total' => (int) $stats->total_biens,
                    'actifs' => (int) $stats->biens_actifs,
                    'sortis' => (int) $stats->biens_sortis,
                ],
                'valorisation' => [
                    'valeur_brute_acquisition' => (float) $stats->valeur_totale_acquisition,
                    'vnc_totale_exercice_courant' => (float) $vncTotale,
                    'devise' => 'XAF',
                ],
                'inventaire' => [
                    'campagnes_en_cours' => $campagnesEnCours,
                ],
            ],
        ]);
    }

    public function patrimoineParCategorie(): JsonResponse
    {
        $data = DB::table('immobilisations')
            ->join('categories_immobilisations', 'immobilisations.categorie_id', '=', 'categories_immobilisations.id')
            ->whereNull('immobilisations.deleted_at')
            ->groupBy('categories_immobilisations.id', 'categories_immobilisations.code', 'categories_immobilisations.libelle')
            ->selectRaw('
                categories_immobilisations.code,
                categories_immobilisations.libelle,
                count(immobilisations.id) as nombre,
                sum(immobilisations.valeur_acquisition) as valeur_brute
            ')
            ->orderByRaw('count(immobilisations.id) desc')
            ->get();

        return response()->json(['data' => $data]);
    }

    public function patrimoineParSite(): JsonResponse
    {
        $data = DB::table('immobilisations')
            ->join('sites', 'immobilisations.site_id', '=', 'sites.id')
            ->whereNull('immobilisations.deleted_at')
            ->groupBy('sites.id', 'sites.code', 'sites.libelle')
            ->selectRaw('
                sites.code,
                sites.libelle,
                count(immobilisations.id) as nombre,
                sum(immobilisations.valeur_acquisition) as valeur_brute
            ')
            ->orderByRaw('count(immobilisations.id) desc')
            ->get();

        return response()->json(['data' => $data]);
    }

    public function patrimoineParStatut(): JsonResponse
    {
        $data = DB::table('immobilisations')
            ->whereNull('deleted_at')
            ->groupBy('statut')
            ->selectRaw('statut, count(*) as nombre, sum(valeur_acquisition) as valeur_brute')
            ->orderByRaw('count(*) desc')
            ->get();

        return response()->json(['data' => $data]);
    }
}
