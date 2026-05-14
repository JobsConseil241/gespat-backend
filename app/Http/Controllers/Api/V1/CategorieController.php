<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Categories\StoreCategorieRequest;
use App\Http\Requests\Categories\UpdateCategorieRequest;
use App\Http\Resources\CategorieImmobilisationResource;
use App\Models\CategorieImmobilisation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class CategorieController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:categories.view', only: ['index', 'show']),
        ];
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = CategorieImmobilisation::query()
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->string('search').'%';
                $q->where(fn ($q) => $q->where('code', 'ilike', $term)->orWhere('libelle', 'ilike', $term));
            })
            ->when($request->filled('parent_id'), fn ($q) => $q->where('parent_id', $request->integer('parent_id')))
            ->when($request->filled('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->orderBy('code');

        return CategorieImmobilisationResource::collection($query->paginate($request->integer('per_page', 50)));
    }

    public function store(StoreCategorieRequest $request): JsonResponse
    {
        $cat = CategorieImmobilisation::create($request->validated());

        return (new CategorieImmobilisationResource($cat->fresh()))
            ->response()
            ->setStatusCode(201);
    }

    public function show(CategorieImmobilisation $categorie): CategorieImmobilisationResource
    {
        return new CategorieImmobilisationResource($categorie);
    }

    public function update(UpdateCategorieRequest $request, CategorieImmobilisation $categorie): CategorieImmobilisationResource
    {
        $categorie->update($request->validated());

        return new CategorieImmobilisationResource($categorie->fresh());
    }

    public function destroy(Request $request, CategorieImmobilisation $categorie): JsonResponse
    {
        abort_unless($request->user()->can('categories.delete'), 403);

        if ($categorie->enfants()->exists()) {
            return response()->json([
                'message' => 'Impossible de supprimer : la catégorie contient des sous-catégories.',
            ], 422);
        }

        $categorie->delete();

        return response()->json(['message' => 'Catégorie supprimée.']);
    }
}
