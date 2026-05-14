<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Immobilisation;
use App\Models\ImmobilisationDocument;
use App\Models\ImmobilisationPhoto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Storage;

class ImmobilisationMediaController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:immobilisations.update'),
        ];
    }

    public function uploadPhoto(Request $request, Immobilisation $immobilisation): JsonResponse
    {
        $data = $request->validate([
            'photo' => ['required', 'file', 'image', 'max:8192'], // 8 Mo
            'legende' => ['nullable', 'string', 'max:255'],
            'ordre' => ['nullable', 'integer', 'min:0', 'max:99'],
            'principale' => ['nullable', 'boolean'],
        ]);

        $path = $request->file('photo')->store("immobilisations/{$immobilisation->id}/photos", 'public');

        $photo = ImmobilisationPhoto::create([
            'immobilisation_id' => $immobilisation->id,
            'path' => $path,
            'legende' => $data['legende'] ?? null,
            'ordre' => $data['ordre'] ?? 0,
            'uploaded_by' => $request->user()->id,
        ]);

        if ($request->boolean('principale')) {
            $immobilisation->update(['photo_principale_path' => $path]);
        }

        return response()->json(['data' => $photo], 201);
    }

    public function deletePhoto(Immobilisation $immobilisation, ImmobilisationPhoto $photo): JsonResponse
    {
        abort_unless($photo->immobilisation_id === $immobilisation->id, 404);

        Storage::disk('public')->delete($photo->path);
        if ($immobilisation->photo_principale_path === $photo->path) {
            $immobilisation->update(['photo_principale_path' => null]);
        }
        $photo->delete();

        return response()->json(['message' => 'Photo supprimée.']);
    }

    public function uploadDocument(Request $request, Immobilisation $immobilisation): JsonResponse
    {
        $data = $request->validate([
            'document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx', 'max:20480'], // 20 Mo
            'type' => ['required', 'in:facture,garantie,manuel,pv_reception,pv_reforme,autre'],
            'libelle' => ['required', 'string', 'max:255'],
        ]);

        $file = $request->file('document');
        $path = $file->store("immobilisations/{$immobilisation->id}/documents", 'public');

        $doc = ImmobilisationDocument::create([
            'immobilisation_id' => $immobilisation->id,
            'type' => $data['type'],
            'libelle' => $data['libelle'],
            'path' => $path,
            'taille_octets' => $file->getSize(),
            'uploaded_by' => $request->user()->id,
        ]);

        return response()->json(['data' => $doc], 201);
    }

    public function deleteDocument(Immobilisation $immobilisation, ImmobilisationDocument $document): JsonResponse
    {
        abort_unless($document->immobilisation_id === $immobilisation->id, 404);

        Storage::disk('public')->delete($document->path);
        $document->delete();

        return response()->json(['message' => 'Document supprimé.']);
    }
}
