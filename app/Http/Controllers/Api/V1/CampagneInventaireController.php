<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CampagneInventaireResource;
use App\Models\CampagneInventaire;
use App\Models\FicheInventaire;
use App\Models\Immobilisation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;

class CampagneInventaireController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:campagnes.view', only: ['index', 'show', 'avancement', 'fiches']),
            new Middleware('can:campagnes.create', only: ['store', 'generateFiches']),
            new Middleware('can:campagnes.update', only: ['update']),
            new Middleware('can:campagnes.cloturer', only: ['cloturer']),
        ];
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = CampagneInventaire::query()
            ->withCount('fiches')
            ->when($request->filled('statut'), fn ($q) => $q->where('statut', $request->string('statut')))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))
            ->latest('date_debut_prevue');

        return CampagneInventaireResource::collection($query->paginate($request->integer('per_page', 25)));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:30', 'unique:campagnes_inventaire,code'],
            'libelle' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'in:general_annuel,partiel,tournant,contradictoire'],
            'date_debut_prevue' => ['required', 'date'],
            'date_fin_prevue' => ['required', 'date', 'after_or_equal:date_debut_prevue'],
            'perimetre' => ['required', 'array'],
            'perimetre.sites' => ['nullable', 'array'],
            'perimetre.sites.*' => ['integer', 'exists:sites,id'],
            'perimetre.categories' => ['nullable', 'array'],
            'perimetre.categories.*' => ['integer', 'exists:categories_immobilisations,id'],
            'responsable_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $campagne = CampagneInventaire::create([
            ...$data,
            'statut' => 'preparation',
        ]);

        return response()->json(['data' => new CampagneInventaireResource($campagne)], 201);
    }

    public function show(CampagneInventaire $campagne): CampagneInventaireResource
    {
        return new CampagneInventaireResource($campagne->loadCount('fiches'));
    }

    public function update(Request $request, CampagneInventaire $campagne): JsonResponse
    {
        abort_if(in_array($campagne->statut, ['cloturee', 'annulee'], true), 422,
            'Campagne déjà clôturée ou annulée.');

        $data = $request->validate([
            'libelle' => ['sometimes', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'date_debut_prevue' => ['sometimes', 'date'],
            'date_fin_prevue' => ['sometimes', 'date'],
            'perimetre' => ['sometimes', 'array'],
            'responsable_id' => ['nullable', 'integer', 'exists:users,id'],
            'statut' => ['sometimes', 'in:preparation,en_cours'],
        ]);

        $campagne->update($data);

        return response()->json(['data' => new CampagneInventaireResource($campagne->fresh())]);
    }

    /**
     * Génère automatiquement les fiches attendues pour le périmètre.
     * Une fiche par immobilisation présente dans le périmètre.
     */
    public function generateFiches(CampagneInventaire $campagne): JsonResponse
    {
        abort_if($campagne->statut !== 'preparation', 422,
            'Les fiches ne peuvent être générées que sur une campagne en préparation.');

        $perimetre = $campagne->perimetre ?? [];
        $sites = $perimetre['sites'] ?? [];
        $categories = $perimetre['categories'] ?? [];

        $count = DB::transaction(function () use ($campagne, $sites, $categories) {
            // Évite les doublons si déjà généré.
            FicheInventaire::where('campagne_id', $campagne->id)->delete();

            $immos = Immobilisation::query()
                ->when(! empty($sites), fn ($q) => $q->whereIn('site_id', $sites))
                ->when(! empty($categories), fn ($q) => $q->whereIn('categorie_id', $categories))
                ->whereIn('statut', ['actif', 'en_maintenance'])
                ->get(['id', 'code_inventaire', 'localisation_id', 'service_id']);

            $rows = $immos->map(fn ($immo) => [
                'campagne_id' => $campagne->id,
                'immobilisation_id' => $immo->id,
                'code_attendu' => $immo->code_inventaire,
                'localisation_attendue_id' => $immo->localisation_id,
                'service_attendu_id' => $immo->service_id,
                'statut' => 'a_inventorier',
                'version' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ])->all();

            if (! empty($rows)) {
                FicheInventaire::insert($rows);
            }

            $campagne->update(['statut' => 'en_cours', 'date_debut_reelle' => now()]);

            return count($rows);
        });

        return response()->json([
            'data' => [
                'fiches_generees' => $count,
                'campagne' => new CampagneInventaireResource($campagne->fresh()),
            ],
        ]);
    }

    public function avancement(CampagneInventaire $campagne): JsonResponse
    {
        $stats = DB::table('fiches_inventaire')
            ->where('campagne_id', $campagne->id)
            ->selectRaw('statut, count(*) as total')
            ->groupBy('statut')
            ->pluck('total', 'statut');

        $total = $stats->sum();
        $traites = $total - ($stats['a_inventorier'] ?? 0);

        return response()->json([
            'data' => [
                'total' => $total,
                'par_statut' => $stats,
                'taux_avancement_pct' => $total > 0 ? round(($traites / $total) * 100, 2) : 0,
            ],
        ]);
    }

    public function fiches(Request $request, CampagneInventaire $campagne): AnonymousResourceCollection
    {
        $query = $campagne->fiches()
            ->with('immobilisation:id,code_inventaire,libelle,categorie_id,site_id')
            ->when($request->filled('statut'), fn ($q) => $q->where('statut', $request->string('statut')))
            ->orderBy('id');

        return \App\Http\Resources\FicheInventaireResource::collection(
            $query->paginate($request->integer('per_page', 50))
        );
    }

    public function cloturer(Request $request, CampagneInventaire $campagne): JsonResponse
    {
        abort_if($campagne->statut !== 'en_cours', 422,
            'Seule une campagne en cours peut être clôturée.');

        $campagne->update([
            'statut' => 'cloturee',
            'date_fin_reelle' => now(),
            'cloturee_par' => $request->user()->id,
            'cloturee_le' => now(),
        ]);

        return response()->json(['data' => new CampagneInventaireResource($campagne)]);
    }
}
