<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Etiquette;
use App\Models\Immobilisation;
use App\Models\LotEtiquettes;
use App\Services\Codification\EtiquetteService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class EtiquetteController extends Controller implements HasMiddleware
{
    public function __construct(private readonly EtiquetteService $svc) {}

    public static function middleware(): array
    {
        return [
            new Middleware('can:etiquettes.generer', only: ['createLot', 'listLots']),
            new Middleware('can:etiquettes.imprimer', only: ['genererPdf', 'marquerImprime']),
        ];
    }

    public function listLots(): JsonResponse
    {
        return response()->json([
            'data' => LotEtiquettes::with(['genereur', 'imprimeur'])->latest('id')->paginate(25),
        ]);
    }

    public function createLot(Request $request): JsonResponse
    {
        $data = $request->validate([
            'libelle' => ['required', 'string', 'max:200'],
            'format_etiquette' => ['required', 'in:62x29,38x90,A4_avery_24,A4_avery_30'],
            'immobilisation_ids' => ['required', 'array', 'min:1'],
            'immobilisation_ids.*' => ['integer', 'exists:immobilisations,id'],
        ]);

        $lot = DB::transaction(function () use ($data, $request) {
            $lot = LotEtiquettes::create([
                'libelle' => $data['libelle'],
                'format_etiquette' => $data['format_etiquette'],
                'nombre_etiquettes' => count($data['immobilisation_ids']),
                'genere_par' => $request->user()->id,
                'genere_le' => now(),
            ]);

            Immobilisation::whereIn('id', $data['immobilisation_ids'])
                ->get(['id', 'code_inventaire'])
                ->each(fn ($immo) => $this->svc->creerEtiquette($immo->code_inventaire, $immo, $lot->id));

            return $lot;
        });

        return response()->json(['data' => $lot->load('etiquettes')], 201);
    }

    public function genererPdf(LotEtiquettes $lot): Response
    {
        $etiquettes = $lot->etiquettes()->with('immobilisation:id,code_inventaire,libelle')->get()
            ->map(fn ($e) => [
                'code' => $e->code_inventaire,
                'libelle' => $e->immobilisation?->libelle ?? $e->code_inventaire,
                'qr' => $this->svc->qrCodeBase64($e->qr_payload ?? $e->code_inventaire, 150),
                'barcode' => $this->svc->barcodeBase64($e->barcode_payload ?? $e->code_inventaire),
            ]);

        $pdf = Pdf::loadView('etiquettes.planche-a4', [
            'lot' => $lot,
            'etiquettes' => $etiquettes,
            'format' => config('gespat.etiquettes.formats.'.$lot->format_etiquette),
        ])->setPaper('a4', 'portrait');

        $path = 'etiquettes/lot-'.$lot->id.'-'.now()->format('Ymd-His').'.pdf';
        Storage::put($path, $pdf->output());
        $lot->update(['pdf_path' => $path]);

        return $pdf->stream("lot-{$lot->id}.pdf");
    }

    public function marquerImprime(Request $request, LotEtiquettes $lot): JsonResponse
    {
        $lot->update([
            'imprime_par' => $request->user()->id,
            'imprime_le' => now(),
        ]);

        $lot->etiquettes()->where('etat', 'genere')->update(['etat' => 'imprime']);

        return response()->json(['data' => $lot->fresh()]);
    }

    public function reediter(Request $request, Etiquette $etiquette): JsonResponse
    {
        $nouvelle = $this->svc->creerEtiquette(
            $etiquette->code_inventaire,
            $etiquette->immobilisation,
            $etiquette->lot_id
        );
        $nouvelle->update(['remplace_etiquette_id' => $etiquette->id]);
        $etiquette->update(['etat' => 'remplace']);

        return response()->json(['data' => $nouvelle, 'message' => 'Étiquette rééditée.']);
    }
}
