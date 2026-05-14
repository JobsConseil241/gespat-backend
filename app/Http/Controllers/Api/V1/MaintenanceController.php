<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Maintenance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class MaintenanceController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:immobilisations.view', only: ['index', 'show']),
            new Middleware('can:immobilisations.update', only: ['store', 'update', 'destroy']),
        ];
    }

    public function index(Request $request): JsonResponse
    {
        $query = Maintenance::query()
            ->with('immobilisation:id,code_inventaire,libelle')
            ->when($request->filled('immobilisation_id'), fn ($q) => $q->where('immobilisation_id', $request->integer('immobilisation_id')))
            ->when($request->filled('statut'), fn ($q) => $q->where('statut', $request->string('statut')))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))
            ->when($request->boolean('echeances_proches'), fn ($q) => $q->where('prochaine_echeance', '<=', now()->addDays(30))->whereIn('statut', ['planifiee', 'en_cours']))
            ->orderBy('date_prevue', 'desc');

        return response()->json(['data' => $query->paginate($request->integer('per_page', 25))]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'immobilisation_id' => ['required', 'integer', 'exists:immobilisations,id'],
            'type' => ['required', 'in:preventive,curative,controle_technique'],
            'date_prevue' => ['nullable', 'date'],
            'date_realisation' => ['nullable', 'date'],
            'description_intervention' => ['nullable', 'string'],
            'cout' => ['nullable', 'numeric', 'min:0'],
            'prestataire' => ['nullable', 'string', 'max:200'],
            'kilometrage' => ['nullable', 'integer', 'min:0'],
            'prochaine_echeance' => ['nullable', 'date'],
            'statut' => ['nullable', 'in:planifiee,en_cours,terminee,annulee'],
        ]);

        $maint = Maintenance::create([
            ...$data,
            'statut' => $data['statut'] ?? 'planifiee',
            'cree_par_user_id' => $request->user()->id,
        ]);

        return response()->json(['data' => $maint->load('immobilisation')], 201);
    }

    public function show(Maintenance $maintenance): JsonResponse
    {
        return response()->json(['data' => $maintenance->load('immobilisation')]);
    }

    public function update(Request $request, Maintenance $maintenance): JsonResponse
    {
        $data = $request->validate([
            'date_prevue' => ['nullable', 'date'],
            'date_realisation' => ['nullable', 'date'],
            'description_intervention' => ['nullable', 'string'],
            'cout' => ['nullable', 'numeric', 'min:0'],
            'prestataire' => ['nullable', 'string', 'max:200'],
            'kilometrage' => ['nullable', 'integer', 'min:0'],
            'prochaine_echeance' => ['nullable', 'date'],
            'statut' => ['nullable', 'in:planifiee,en_cours,terminee,annulee'],
        ]);

        $maintenance->update($data);

        return response()->json(['data' => $maintenance->fresh('immobilisation')]);
    }

    public function destroy(Maintenance $maintenance): JsonResponse
    {
        $maintenance->delete();

        return response()->json(['message' => 'Maintenance supprimée.']);
    }
}
