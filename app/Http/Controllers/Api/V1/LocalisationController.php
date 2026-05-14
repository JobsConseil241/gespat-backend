<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Localisations\StoreLocalisationRequest;
use App\Http\Requests\Localisations\UpdateLocalisationRequest;
use App\Http\Resources\LocalisationResource;
use App\Models\Localisation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class LocalisationController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:localisations.view', only: ['index', 'show']),
        ];
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Localisation::query()
            ->with('site')
            ->when($request->filled('site_id'), fn ($q) => $q->where('site_id', $request->integer('site_id')))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->string('search').'%';
                $q->where(fn ($q) => $q->where('code', 'ilike', $term)->orWhere('libelle', 'ilike', $term));
            })
            ->orderBy('path');

        return LocalisationResource::collection($query->paginate($request->integer('per_page', 50)));
    }

    public function store(StoreLocalisationRequest $request): JsonResponse
    {
        $localisation = Localisation::create($request->validated());

        return (new LocalisationResource($localisation->fresh()))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Localisation $localisation): LocalisationResource
    {
        return new LocalisationResource($localisation->load(['site', 'parent', 'enfants']));
    }

    public function update(UpdateLocalisationRequest $request, Localisation $localisation): LocalisationResource
    {
        $localisation->update($request->validated());

        return new LocalisationResource($localisation->fresh());
    }

    public function destroy(Request $request, Localisation $localisation): JsonResponse
    {
        abort_unless($request->user()->can('localisations.delete'), 403);

        if ($localisation->enfants()->exists()) {
            return response()->json([
                'message' => 'Impossible de supprimer : la localisation contient des sous-localisations.',
            ], 422);
        }

        $localisation->delete();

        return response()->json(['message' => 'Localisation supprimée.']);
    }
}
