<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fournisseurs\StoreFournisseurRequest;
use App\Http\Requests\Fournisseurs\UpdateFournisseurRequest;
use App\Http\Resources\FournisseurResource;
use App\Models\Fournisseur;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class FournisseurController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:fournisseurs.view', only: ['index', 'show']),
        ];
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Fournisseur::query()
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->string('search').'%';
                $q->where(function ($q) use ($term) {
                    $q->where('raison_sociale', 'ilike', $term)
                        ->orWhere('niu', 'ilike', $term)
                        ->orWhere('email', 'ilike', $term);
                });
            })
            ->when($request->filled('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->orderBy('raison_sociale');

        return FournisseurResource::collection($query->paginate($request->integer('per_page', 25)));
    }

    public function store(StoreFournisseurRequest $request): JsonResponse
    {
        $f = Fournisseur::create($request->validated());

        return (new FournisseurResource($f->fresh()))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Fournisseur $fournisseur): FournisseurResource
    {
        return new FournisseurResource($fournisseur);
    }

    public function update(UpdateFournisseurRequest $request, Fournisseur $fournisseur): FournisseurResource
    {
        $fournisseur->update($request->validated());

        return new FournisseurResource($fournisseur->fresh());
    }

    public function destroy(Request $request, Fournisseur $fournisseur): JsonResponse
    {
        abort_unless($request->user()->can('fournisseurs.delete'), 403);
        $fournisseur->delete();

        return response()->json(['message' => 'Fournisseur supprimé.']);
    }
}
