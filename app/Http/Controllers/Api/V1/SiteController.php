<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sites\StoreSiteRequest;
use App\Http\Requests\Sites\UpdateSiteRequest;
use App\Http\Resources\LocalisationResource;
use App\Http\Resources\SiteResource;
use App\Models\Site;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class SiteController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:sites.view', only: ['index', 'show', 'localisations']),
        ];
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Site::query()
            ->withCount('localisations')
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->string('search').'%';
                $q->where(function ($q) use ($term) {
                    $q->where('code', 'ilike', $term)
                        ->orWhere('libelle', 'ilike', $term)
                        ->orWhere('ville', 'ilike', $term);
                });
            })
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))
            ->when($request->filled('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->orderBy('code');

        return SiteResource::collection($query->paginate($request->integer('per_page', 25)));
    }

    public function store(StoreSiteRequest $request): JsonResponse
    {
        $site = Site::create($request->validated());

        return (new SiteResource($site->fresh()))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Site $site): SiteResource
    {
        return new SiteResource($site->loadCount('localisations')->load('responsable'));
    }

    public function update(UpdateSiteRequest $request, Site $site): SiteResource
    {
        $site->update($request->validated());

        return new SiteResource($site->fresh());
    }

    public function destroy(Request $request, Site $site): JsonResponse
    {
        abort_unless($request->user()->can('sites.delete'), 403);
        $site->delete();

        return response()->json(['message' => 'Site supprimé.']);
    }

    public function localisations(Site $site): AnonymousResourceCollection
    {
        $racines = $site->localisations()
            ->whereNull('parent_id')
            ->with('enfants.enfants.enfants')
            ->orderBy('code')
            ->get();

        return LocalisationResource::collection($racines);
    }
}
