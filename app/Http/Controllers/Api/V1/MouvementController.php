<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Immobilisation;
use App\Models\Mouvement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;

class MouvementController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:immobilisations.view', only: ['index', 'show']),
            new Middleware('can:immobilisations.transfer', only: ['store', 'valider']),
        ];
    }

    public function index(Request $request): JsonResponse
    {
        $query = Mouvement::query()
            ->with(['immobilisation:id,code_inventaire,libelle', 'siteOrigine:id,code', 'siteDestination:id,code'])
            ->when($request->filled('immobilisation_id'), fn ($q) => $q->where('immobilisation_id', $request->integer('immobilisation_id')))
            ->when($request->filled('type'), fn ($q) => $q->where('type_mouvement', $request->string('type')))
            ->when($request->filled('statut'), fn ($q) => $q->where('statut', $request->string('statut')))
            ->latest('date_mouvement');

        return response()->json(['data' => $query->paginate($request->integer('per_page', 25))]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'immobilisation_id' => ['required', 'integer', 'exists:immobilisations,id'],
            'type_mouvement' => ['required', 'in:entree,transfert,sortie_temporaire,retour,changement_etat,maintenance_entree,maintenance_sortie'],
            'date_mouvement' => ['required', 'date'],
            'motif' => ['nullable', 'string'],
            'site_destination_id' => ['nullable', 'integer', 'exists:sites,id'],
            'localisation_destination_id' => ['nullable', 'integer', 'exists:localisations,id'],
            'service_destination_id' => ['nullable', 'integer', 'exists:services,id'],
        ]);

        $mouvement = DB::transaction(function () use ($data, $request) {
            $immo = Immobilisation::findOrFail($data['immobilisation_id']);

            $mouvement = Mouvement::create([
                ...$data,
                'site_origine_id' => $immo->site_id,
                'localisation_origine_id' => $immo->localisation_id,
                'service_origine_id' => $immo->service_id,
                'statut' => 'propose',
                'cree_par_user_id' => $request->user()->id,
            ]);

            return $mouvement;
        });

        return response()->json(['data' => $mouvement], 201);
    }

    public function show(Mouvement $mouvement): JsonResponse
    {
        return response()->json(['data' => $mouvement->load(['immobilisation', 'siteOrigine', 'siteDestination', 'valideur'])]);
    }

    /**
     * Validation d'un mouvement → applique le changement sur l'immobilisation.
     */
    public function valider(Request $request, Mouvement $mouvement): JsonResponse
    {
        abort_if($mouvement->statut !== 'propose', 422, 'Mouvement déjà traité.');

        DB::transaction(function () use ($mouvement, $request) {
            $immo = $mouvement->immobilisation;
            $updates = array_filter([
                'site_id' => $mouvement->site_destination_id,
                'localisation_id' => $mouvement->localisation_destination_id,
                'service_id' => $mouvement->service_destination_id,
            ], fn ($v) => $v !== null);

            if (! empty($updates)) {
                $immo->update($updates);
            }

            $mouvement->update([
                'statut' => 'execute',
                'valide_par_user_id' => $request->user()->id,
                'valide_at' => now(),
            ]);
        });

        return response()->json(['data' => $mouvement->fresh(['immobilisation']), 'message' => 'Mouvement validé et appliqué.']);
    }

    public function refuser(Request $request, Mouvement $mouvement): JsonResponse
    {
        abort_if($mouvement->statut !== 'propose', 422, 'Mouvement déjà traité.');
        $mouvement->update(['statut' => 'refuse', 'valide_par_user_id' => $request->user()->id, 'valide_at' => now()]);

        return response()->json(['data' => $mouvement->fresh()]);
    }
}
