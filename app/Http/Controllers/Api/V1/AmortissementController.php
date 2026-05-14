<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AmortissementAnnuel;
use App\Models\Immobilisation;
use App\Services\Amortissement\AmortissementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;

class AmortissementController extends Controller implements HasMiddleware
{
    public function __construct(private readonly AmortissementService $svc) {}

    public static function middleware(): array
    {
        return [
            new Middleware('can:immobilisations.view', only: ['simuler', 'showByImmobilisation', 'etatExercice']),
            new Middleware('can:amortissements.calculer', only: ['calculer', 'calculerTous']),
            new Middleware('can:amortissements.valider', only: ['valider']),
        ];
    }

    public function simuler(Request $request): JsonResponse
    {
        $data = $request->validate([
            'immobilisation_id' => ['required', 'integer', 'exists:immobilisations,id'],
            'exercice_max' => ['nullable', 'integer', 'min:1990', 'max:2100'],
        ]);

        $immo = Immobilisation::findOrFail($data['immobilisation_id']);
        $resultats = $this->svc->calculerImmobilisation($immo, $data['exercice_max'] ?? now()->year);

        return response()->json(['data' => $resultats]);
    }

    public function calculer(Request $request, Immobilisation $immobilisation): JsonResponse
    {
        $exercice = $request->integer('exercice_max', now()->year);
        $count = $this->svc->persister($immobilisation, $exercice);

        return response()->json([
            'data' => ['lignes_ecrites' => $count, 'exercice_max' => $exercice],
            'message' => "{$count} exercice(s) d'amortissement calculés.",
        ]);
    }

    public function calculerTous(Request $request): JsonResponse
    {
        $exercice = $request->integer('exercice_max', now()->year);
        $stats = ['traitees' => 0, 'lignes' => 0];

        Immobilisation::where('methode_amortissement', '<>', 'non_amortissable')
            ->whereNotNull('date_mise_en_service')
            ->whereNotNull('duree_amortissement_mois')
            ->chunk(100, function ($immos) use ($exercice, &$stats) {
                foreach ($immos as $immo) {
                    $stats['lignes'] += $this->svc->persister($immo, $exercice);
                    $stats['traitees']++;
                }
            });

        return response()->json(['data' => $stats]);
    }

    public function showByImmobilisation(Immobilisation $immobilisation): JsonResponse
    {
        $lignes = AmortissementAnnuel::where('immobilisation_id', $immobilisation->id)
            ->orderBy('exercice')
            ->get();

        return response()->json(['data' => $lignes]);
    }

    public function valider(Request $request): JsonResponse
    {
        $data = $request->validate([
            'exercice' => ['required', 'integer'],
            'immobilisation_ids' => ['nullable', 'array'],
            'immobilisation_ids.*' => ['integer', 'exists:immobilisations,id'],
        ]);

        $query = AmortissementAnnuel::where('exercice', $data['exercice'])->where('est_valide', false);
        if (! empty($data['immobilisation_ids'])) {
            $query->whereIn('immobilisation_id', $data['immobilisation_ids']);
        }

        $updated = $query->update([
            'est_valide' => true,
            'valide_par' => $request->user()->id,
            'valide_le' => now(),
        ]);

        return response()->json(['data' => ['lignes_validees' => $updated]]);
    }

    public function etatExercice(int $exercice): JsonResponse
    {
        $stats = DB::table('amortissements_annuels')
            ->where('exercice', $exercice)
            ->selectRaw('
                count(*) as nombre_immo,
                sum(dotation_exercice) as total_dotations,
                sum(cumul_amortissement_fin) as total_cumul,
                sum(vnc_fin) as total_vnc,
                sum(case when est_valide then 1 else 0 end) as valides
            ')
            ->first();

        return response()->json(['data' => $stats]);
    }
}
