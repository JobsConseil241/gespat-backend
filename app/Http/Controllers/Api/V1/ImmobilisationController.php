<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Immobilisations\StoreImmobilisationRequest;
use App\Http\Requests\Immobilisations\UpdateImmobilisationRequest;
use App\Http\Resources\ImmobilisationResource;
use App\Models\CategorieImmobilisation;
use App\Models\Immobilisation;
use App\Models\Site;
use App\Services\Codification\CodificationService;
use App\Services\Codification\EtiquetteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;

class ImmobilisationController extends Controller implements HasMiddleware
{
    public function __construct(
        private readonly CodificationService $codification,
        private readonly EtiquetteService $etiquettes,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('can:immobilisations.view', only: ['index', 'show', 'byCode']),
        ];
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Immobilisation::query()
            ->with(['categorie', 'site', 'localisation', 'service'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->string('search').'%';
                $q->where(function ($q) use ($term) {
                    $q->where('code_inventaire', 'ilike', $term)
                        ->orWhere('libelle', 'ilike', $term)
                        ->orWhere('numero_serie', 'ilike', $term)
                        ->orWhere('numero_immatriculation', 'ilike', $term);
                });
            })
            ->when($request->filled('site_id'), fn ($q) => $q->where('site_id', $request->integer('site_id')))
            ->when($request->filled('categorie_id'), fn ($q) => $q->where('categorie_id', $request->integer('categorie_id')))
            ->when($request->filled('statut'), fn ($q) => $q->where('statut', $request->string('statut')))
            ->when($request->filled('etat_physique'), fn ($q) => $q->where('etat_physique', $request->string('etat_physique')))
            ->latest('id');

        return ImmobilisationResource::collection($query->paginate($request->integer('per_page', 25)));
    }

    public function store(StoreImmobilisationRequest $request): JsonResponse
    {
        $data = $request->validated();

        $immo = DB::transaction(function () use ($data, $request) {
            if (empty($data['code_inventaire'])) {
                $categorie = CategorieImmobilisation::findOrFail($data['categorie_id']);
                $site = Site::findOrFail($data['site_id']);
                $data['code_inventaire'] = $this->codification->genererCode($categorie, $site);
            }

            $data['qr_payload'] = $this->etiquettes->buildQrPayload($data['code_inventaire']);
            $data['created_by'] = $request->user()->id;

            return Immobilisation::create($data);
        });

        return (new ImmobilisationResource($immo->fresh(['categorie', 'site'])))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Immobilisation $immobilisation): ImmobilisationResource
    {
        return new ImmobilisationResource($immobilisation->load([
            'categorie', 'marque', 'modele', 'site', 'localisation', 'service',
            'fournisseur', 'affecteA', 'photos', 'documents', 'caracteristiques', 'bienImmobilier',
        ]));
    }

    public function byCode(string $code): ImmobilisationResource
    {
        $immo = Immobilisation::with(['categorie', 'site', 'localisation', 'service'])
            ->where('code_inventaire', $code)
            ->firstOrFail();

        return new ImmobilisationResource($immo);
    }

    public function update(UpdateImmobilisationRequest $request, Immobilisation $immobilisation): ImmobilisationResource
    {
        $immobilisation->update([
            ...$request->validated(),
            'updated_by' => $request->user()->id,
        ]);

        return new ImmobilisationResource($immobilisation->fresh(['categorie', 'site']));
    }

    public function destroy(Request $request, Immobilisation $immobilisation): JsonResponse
    {
        abort_unless($request->user()->can('immobilisations.delete'), 403);

        if (in_array($immobilisation->statut, ['reforme', 'cede', 'perdu', 'vole', 'detruit'], true)) {
            return response()->json([
                'message' => 'Une immobilisation déjà sortie est archivée — utilisez le module Sorties pour la traçabilité.',
            ], 422);
        }

        $immobilisation->delete();

        return response()->json(['message' => 'Immobilisation supprimée (soft delete).']);
    }
}
