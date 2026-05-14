<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Immobilisation;
use App\Models\Mouvement;
use App\Models\Sortie;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;

class SortieController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:immobilisations.view', only: ['index', 'show']),
            new Middleware('can:immobilisations.sortie', only: ['store', 'valider']),
        ];
    }

    public function index(Request $request): JsonResponse
    {
        $query = Sortie::query()
            ->with('immobilisation:id,code_inventaire,libelle')
            ->when($request->filled('type'), fn ($q) => $q->where('type_sortie', $request->string('type')))
            ->when($request->filled('statut'), fn ($q) => $q->where('statut', $request->string('statut')))
            ->latest('date_decision');

        return response()->json(['data' => $query->paginate($request->integer('per_page', 25))]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'immobilisation_id' => ['required', 'integer', 'exists:immobilisations,id'],
            'type_sortie' => ['required', 'in:reforme,cession,perte,vol,destruction'],
            'date_decision' => ['required', 'date'],
            'numero_decision' => ['nullable', 'string', 'max:100'],
            'motif' => ['required', 'string'],
            'prix_cession' => ['nullable', 'numeric', 'min:0'],
            'acquereur_nom' => ['nullable', 'string', 'max:200'],
            'acquereur_contact' => ['nullable', 'string', 'max:150'],
            'commission_membres' => ['nullable', 'array'],
        ]);

        $sortie = Sortie::create([
            ...$data,
            'statut' => 'proposee',
            'propose_par' => $request->user()->id,
        ]);

        return response()->json(['data' => $sortie->load('immobilisation')], 201);
    }

    public function show(Sortie $sortie): JsonResponse
    {
        return response()->json(['data' => $sortie->load(['immobilisation', 'valideur'])]);
    }

    /**
     * Validation officielle d'une sortie → bascule le statut de l'immobilisation
     * (reforme/cede/perdu/vole/detruit) et trace un mouvement.
     */
    public function valider(Request $request, Sortie $sortie): JsonResponse
    {
        abort_if($sortie->statut !== 'proposee', 422, 'Sortie déjà traitée.');

        DB::transaction(function () use ($sortie, $request) {
            $sortie->update([
                'statut' => 'executee',
                'valide_par' => $request->user()->id,
                'valide_le' => now(),
            ]);

            $statutImmo = match ($sortie->type_sortie) {
                'reforme' => 'reforme',
                'cession' => 'cede',
                'perte' => 'perdu',
                'vol' => 'vole',
                'destruction' => 'detruit',
            };

            $sortie->immobilisation->update(['statut' => $statutImmo]);

            Mouvement::create([
                'immobilisation_id' => $sortie->immobilisation_id,
                'type_mouvement' => match ($sortie->type_sortie) {
                    'reforme' => 'reforme',
                    'cession' => 'cession',
                    default => 'perte',
                },
                'date_mouvement' => $sortie->date_decision,
                'motif' => $sortie->motif,
                'site_origine_id' => $sortie->immobilisation->site_id,
                'statut' => 'execute',
                'valide_par_user_id' => $request->user()->id,
                'valide_at' => now(),
                'cree_par_user_id' => $request->user()->id,
            ]);
        });

        return response()->json(['data' => $sortie->fresh('immobilisation')]);
    }
}
