<?php

namespace App\Http\Controllers\Api\V1;

use App\Exports\ImmobilisationsExport;
use App\Http\Controllers\Controller;
use App\Models\Immobilisation;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class ReportController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:reports.export'),
        ];
    }

    public function exportImmobilisationsExcel(Request $request): BinaryFileResponse
    {
        $filename = 'etat-patrimoine-'.now()->format('Y-m-d').'.xlsx';

        return Excel::download(
            new ImmobilisationsExport(
                $request->integer('site_id') ?: null,
                $request->integer('categorie_id') ?: null,
                $request->string('statut')->toString() ?: null,
            ),
            $filename
        );
    }

    public function etatPatrimoinePdf(Request $request): Response
    {
        $immos = Immobilisation::query()
            ->with(['categorie:id,code,libelle', 'site:id,code,libelle', 'service:id,code,libelle'])
            ->when($request->integer('site_id'), fn ($q, $id) => $q->where('site_id', $id))
            ->when($request->integer('categorie_id'), fn ($q, $id) => $q->where('categorie_id', $id))
            ->when($request->string('statut')->toString(), fn ($q, $s) => $q->where('statut', $s))
            ->orderBy('code_inventaire')
            ->limit(2000)
            ->get();

        $totaux = [
            'nombre' => $immos->count(),
            'valeur_brute' => $immos->sum('valeur_acquisition'),
        ];

        $pdf = Pdf::loadView('reports.etat-patrimoine', [
            'immobilisations' => $immos,
            'totaux' => $totaux,
            'arrete_au' => now(),
            'organisme' => config('app.name'),
        ])->setPaper('a4', 'landscape');

        return $pdf->stream('etat-patrimoine-'.now()->format('Ymd').'.pdf');
    }
}
