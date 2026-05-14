<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Services\StoreServiceRequest;
use App\Http\Requests\Services\UpdateServiceRequest;
use App\Http\Resources\ServiceResource;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class ServiceController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:services.view', only: ['index', 'show']),
        ];
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Service::query()
            ->with('sitePrincipal')
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->string('search').'%';
                $q->where(fn ($q) => $q->where('code', 'ilike', $term)->orWhere('libelle', 'ilike', $term));
            })
            ->when($request->filled('parent_id'), fn ($q) => $q->where('parent_id', $request->integer('parent_id')))
            ->when($request->filled('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->orderBy('code');

        return ServiceResource::collection($query->paginate($request->integer('per_page', 50)));
    }

    public function store(StoreServiceRequest $request): JsonResponse
    {
        $service = Service::create($request->validated());

        return (new ServiceResource($service->fresh()))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Service $service): ServiceResource
    {
        return new ServiceResource($service->load(['parent', 'sitePrincipal']));
    }

    public function update(UpdateServiceRequest $request, Service $service): ServiceResource
    {
        $service->update($request->validated());

        return new ServiceResource($service->fresh());
    }

    public function destroy(Request $request, Service $service): JsonResponse
    {
        abort_unless($request->user()->can('services.delete'), 403);

        if ($service->enfants()->exists()) {
            return response()->json([
                'message' => 'Impossible de supprimer : le service contient des sous-services.',
            ], 422);
        }

        $service->delete();

        return response()->json(['message' => 'Service supprimé.']);
    }
}
