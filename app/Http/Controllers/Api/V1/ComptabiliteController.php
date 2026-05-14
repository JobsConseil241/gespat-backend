<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Imports\EcrituresComptablesImport;
use App\Models\EtatReconciliation;
use App\Models\ImportComptable;
use App\Models\Immobilisation;
use App\Models\LigneComptableImmobilisation;
use App\Services\Comptabilite\MatchingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ComptabiliteController extends Controller implements HasMiddleware
{
    public function __construct(private readonly MatchingService $matching) {}

    public static function middleware(): array
    {
        return [
            new Middleware('can:compta.import', only: ['storeImport', 'lignes']),
            new Middleware('can:compta.matcher', only: ['executerMatching', 'matcherManuel']),
            new Middleware('can:compta.reconcilier', only: ['genererReconciliation', 'reconciliationExercice', 'ecarts']),
        ];
    }

    public function storeImport(Request $request): JsonResponse
    {
        $data = $request->validate([
            'fichier' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:51200'],
            'exercice' => ['required', 'integer', 'min:1990', 'max:2100'],
            'source' => ['nullable', 'in:sage,dolibarr,manuel,excel,csv'],
        ]);

        $path = $request->file('fichier')->store('imports/comptables', 'public');

        $import = ImportComptable::create([
            'source' => $data['source'] ?? 'excel',
            'fichier_source_path' => $path,
            'exercice' => $data['exercice'],
            'date_import' => now(),
            'importe_par' => $request->user()->id,
            'statut' => 'en_attente',
        ]);

        try {
            Excel::import(new EcrituresComptablesImport($import), $request->file('fichier'));
            $import->update(['statut' => 'traite', 'nombre_lignes_valides' => $import->lignes()->count()]);
        } catch (\Throwable $e) {
            $import->update(['statut' => 'echec']);
            throw $e;
        }

        return response()->json(['data' => $import->fresh()->loadCount('lignes')], 201);
    }

    public function lignes(ImportComptable $import, Request $request): JsonResponse
    {
        $query = $import->lignes()
            ->with('immobilisation:id,code_inventaire,libelle')
            ->when($request->filled('statut'), fn ($q) => $q->where('statut_matching', $request->string('statut')))
            ->orderBy('id');

        return response()->json(['data' => $query->paginate($request->integer('per_page', 50))]);
    }

    public function executerMatching(ImportComptable $import): JsonResponse
    {
        $stats = $this->matching->executer($import);

        return response()->json(['data' => ['statistiques' => $stats, 'import_id' => $import->id]]);
    }

    public function matcherManuel(Request $request, LigneComptableImmobilisation $ligne): JsonResponse
    {
        $data = $request->validate([
            'immobilisation_id' => ['required', 'integer', 'exists:immobilisations,id'],
        ]);

        $ligne->update([
            'immobilisation_id' => $data['immobilisation_id'],
            'statut_matching' => 'matche_manuel',
            'score_matching' => 100,
        ]);

        return response()->json(['data' => $ligne->fresh('immobilisation')]);
    }

    public function ecarts(Request $request): JsonResponse
    {
        $exercice = $request->integer('exercice', now()->year);

        // Lignes comptables sans immobilisation matchée (compta mais pas patrimoine)
        $sansMatch = LigneComptableImmobilisation::whereHas('import', fn ($q) => $q->where('exercice', $exercice))
            ->where('statut_matching', 'non_trouve')
            ->limit(500)
            ->get();

        // Immobilisations sans correspondance comptable (aucune ligne pointant vers elles)
        $immosMatchees = LigneComptableImmobilisation::whereHas('import', fn ($q) => $q->where('exercice', $exercice))
            ->whereNotNull('immobilisation_id')
            ->pluck('immobilisation_id');

        $sansLigne = Immobilisation::query()
            ->where('est_immobilise_comptablement', true)
            ->whereNotIn('id', $immosMatchees)
            ->limit(500)
            ->get(['id', 'code_inventaire', 'libelle', 'valeur_acquisition']);

        return response()->json([
            'data' => [
                'exercice' => $exercice,
                'lignes_compta_sans_immo' => $sansMatch,
                'immos_sans_ligne_compta' => $sansLigne,
            ],
        ]);
    }

    public function genererReconciliation(Request $request): JsonResponse
    {
        $data = $request->validate([
            'exercice' => ['required', 'integer'],
            'date_arrete' => ['required', 'date'],
        ]);

        $valeurCompta = (float) LigneComptableImmobilisation::whereHas('import', fn ($q) => $q->where('exercice', $data['exercice']))
            ->sum('valeur_origine');

        $valeurPatrimoine = (float) Immobilisation::query()
            ->where('est_immobilise_comptablement', true)
            ->whereDate('date_acquisition', '<=', $data['date_arrete'])
            ->sum('valeur_acquisition');

        $etat = EtatReconciliation::updateOrCreate(
            ['exercice' => $data['exercice'], 'date_arrete' => $data['date_arrete']],
            [
                'valeur_brute_compta' => $valeurCompta,
                'valeur_brute_patrimoine' => $valeurPatrimoine,
                'ecart' => $valeurPatrimoine - $valeurCompta,
                'nombre_lignes_compta' => LigneComptableImmobilisation::whereHas('import', fn ($q) => $q->where('exercice', $data['exercice']))->count(),
                'nombre_immo_patrimoine' => Immobilisation::where('est_immobilise_comptablement', true)
                    ->whereDate('date_acquisition', '<=', $data['date_arrete'])
                    ->count(),
                'genere_par' => $request->user()->id,
                'genere_le' => now(),
            ]
        );

        return response()->json(['data' => $etat]);
    }

    public function reconciliationExercice(int $exercice): JsonResponse
    {
        $etats = EtatReconciliation::where('exercice', $exercice)->orderBy('date_arrete', 'desc')->get();

        return response()->json(['data' => $etats]);
    }
}
