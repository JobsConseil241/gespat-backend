<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use OwenIt\Auditing\Models\Audit;

class AuditController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:audit.view'),
        ];
    }

    public function index(Request $request): JsonResponse
    {
        $query = Audit::query()
            ->with('user:id,matricule,nom,prenom,email')
            ->when($request->filled('user_id'), fn ($q) => $q->where('user_id', $request->integer('user_id')))
            ->when($request->filled('entity_type'), fn ($q) => $q->where('auditable_type', 'like', '%'.$request->string('entity_type').'%'))
            ->when($request->filled('action'), fn ($q) => $q->where('event', $request->string('action')))
            ->when($request->filled('date_debut'), fn ($q) => $q->where('created_at', '>=', $request->string('date_debut')))
            ->when($request->filled('date_fin'), fn ($q) => $q->where('created_at', '<=', $request->string('date_fin').' 23:59:59'))
            ->latest('id');

        $audits = $query->paginate($request->integer('per_page', 50));

        return response()->json([
            'data' => $audits->items(),
            'meta' => [
                'current_page' => $audits->currentPage(),
                'last_page' => $audits->lastPage(),
                'per_page' => $audits->perPage(),
                'total' => $audits->total(),
            ],
        ]);
    }

    public function stats(Request $request): JsonResponse
    {
        $exercice = $request->integer('annee', now()->year);

        $parAction = Audit::query()
            ->selectRaw('event, count(*) as nb')
            ->whereYear('created_at', $exercice)
            ->groupBy('event')
            ->get();

        $parJour = Audit::query()
            ->selectRaw("date_trunc('day', created_at) as jour, count(*) as nb")
            ->whereYear('created_at', $exercice)
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('jour')
            ->orderBy('jour')
            ->get();

        return response()->json([
            'data' => [
                'par_action' => $parAction,
                'par_jour_30' => $parJour,
                'total' => Audit::whereYear('created_at', $exercice)->count(),
            ],
        ]);
    }
}
