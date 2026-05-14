<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CategorieImmobilisation;
use App\Models\PlanCodification;
use App\Models\Site;
use App\Services\Codification\CodificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;

class PlanCodificationController extends Controller implements HasMiddleware
{
    public function __construct(private readonly CodificationService $codification) {}

    public static function middleware(): array
    {
        return [
            new Middleware('can:system.manage'),
        ];
    }

    public function index(): JsonResponse
    {
        return response()->json([
            'data' => PlanCodification::orderBy('actif', 'desc')->orderBy('code')->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:30', 'unique:plans_codification,code'],
            'libelle' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'format' => ['required', 'string', 'max:200'],
            'separateur' => ['nullable', 'string', 'max:5'],
            'mode_sequentiel' => ['required', 'in:global,annuel,par_categorie,par_site'],
            'actif' => ['boolean'],
        ]);

        $plan = DB::transaction(function () use ($data) {
            if (! empty($data['actif'])) {
                PlanCodification::where('actif', true)->update(['actif' => false]);
            }

            return PlanCodification::create($data);
        });

        return response()->json(['data' => $plan], 201);
    }

    public function activer(PlanCodification $plan): JsonResponse
    {
        DB::transaction(function () use ($plan) {
            PlanCodification::where('actif', true)->update(['actif' => false]);
            $plan->update(['actif' => true]);
        });

        return response()->json(['data' => $plan->fresh(), 'message' => 'Plan activé.']);
    }

    public function previsualiser(Request $request): JsonResponse
    {
        $data = $request->validate([
            'categorie_id' => ['required', 'integer', 'exists:categories_immobilisations,id'],
            'site_id' => ['required', 'integer', 'exists:sites,id'],
            'plan_id' => ['nullable', 'integer', 'exists:plans_codification,id'],
        ]);

        $plan = isset($data['plan_id']) ? PlanCodification::findOrFail($data['plan_id']) : null;
        $code = $this->codification->previsualiser(
            CategorieImmobilisation::findOrFail($data['categorie_id']),
            Site::findOrFail($data['site_id']),
            $plan
        );

        return response()->json(['data' => ['code_inventaire' => $code]]);
    }
}
