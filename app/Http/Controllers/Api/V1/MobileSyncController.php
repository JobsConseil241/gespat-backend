<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CampagneInventaireResource;
use App\Http\Resources\FicheInventaireResource;
use App\Models\CampagneInventaire;
use App\Models\FicheInventaire;
use App\Models\Immobilisation;
use App\Models\SynchronisationMobile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;

class MobileSyncController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:fiches.scanner'),
        ];
    }

    /**
     * PULL — l'application mobile télécharge la campagne et ses fiches assignées.
     * Réponse minimaliste pour limiter la bande passante mobile.
     */
    public function pull(Request $request, CampagneInventaire $campagne): JsonResponse
    {
        $data = $request->validate([
            'device_id' => ['required', 'string', 'max:100'],
            'since' => ['nullable', 'date'],
        ]);

        $userId = $request->user()->id;
        $since = isset($data['since']) ? \Illuminate\Support\Carbon::parse($data['since']) : null;

        $fiches = $campagne->fiches()
            ->with('immobilisation:id,code_inventaire,libelle,site_id,localisation_id,service_id,categorie_id')
            ->when($since, fn ($q, $since) => $q->where('updated_at', '>', $since))
            ->get();

        // Trace de la sync
        SynchronisationMobile::create([
            'user_id' => $userId,
            'device_id' => $data['device_id'],
            'campagne_id' => $campagne->id,
            'started_at' => now(),
            'completed_at' => now(),
            'direction' => 'pull',
            'items_recus' => $fiches->count(),
            'statut' => 'reussi',
        ]);

        return response()->json([
            'data' => [
                'campagne' => new CampagneInventaireResource($campagne),
                'fiches' => FicheInventaireResource::collection($fiches),
                'pulled_at' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * PUSH — l'application mobile envoie les fiches saisies sur le terrain.
     * Gestion de conflit basée sur le champ `version`.
     */
    public function push(Request $request): JsonResponse
    {
        $data = $request->validate([
            'device_id' => ['required', 'string', 'max:100'],
            'campagne_id' => ['required', 'integer', 'exists:campagnes_inventaire,id'],
            'fiches' => ['required', 'array', 'min:1'],
            'fiches.*.id' => ['nullable', 'integer'],
            'fiches.*.immobilisation_id' => ['nullable', 'integer', 'exists:immobilisations,id'],
            'fiches.*.code_attendu' => ['nullable', 'string'],
            'fiches.*.code_scanne' => ['nullable', 'string'],
            'fiches.*.statut' => ['required', 'in:vu_conforme,vu_ecart,non_trouve,decouverte'],
            'fiches.*.localisation_constatee_id' => ['nullable', 'integer', 'exists:localisations,id'],
            'fiches.*.service_constate_id' => ['nullable', 'integer', 'exists:services,id'],
            'fiches.*.etat_constate' => ['nullable', 'in:neuf,bon,moyen,mauvais,hors_service'],
            'fiches.*.commentaire_inventoriste' => ['nullable', 'string'],
            'fiches.*.photos_inventaire' => ['nullable', 'array'],
            'fiches.*.gps_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'fiches.*.gps_lng' => ['nullable', 'numeric', 'between:-180,180'],
            'fiches.*.inventorie_at' => ['required', 'date'],
            'fiches.*.version_locale' => ['nullable', 'integer'],
        ]);

        $userId = $request->user()->id;
        $sync = SynchronisationMobile::create([
            'user_id' => $userId,
            'device_id' => $data['device_id'],
            'campagne_id' => $data['campagne_id'],
            'started_at' => now(),
            'direction' => 'push',
            'statut' => 'en_cours',
        ]);

        $accepted = 0;
        $conflicts = [];
        $created = [];

        DB::transaction(function () use ($data, $userId, &$accepted, &$conflicts, &$created) {
            foreach ($data['fiches'] as $payload) {
                // Cas A : fiche pré-générée (id connu)
                if (! empty($payload['id'])) {
                    $fiche = FicheInventaire::lockForUpdate()->find($payload['id']);
                    if (! $fiche) {
                        $conflicts[] = ['id' => $payload['id'], 'raison' => 'fiche introuvable'];
                        continue;
                    }

                    if (! empty($payload['version_locale']) && $fiche->version > $payload['version_locale']) {
                        $conflicts[] = [
                            'id' => $fiche->id,
                            'raison' => 'version_obsolete',
                            'version_serveur' => $fiche->version,
                        ];
                        continue;
                    }

                    $fiche->update([
                        'statut' => $payload['statut'],
                        'localisation_constatee_id' => $payload['localisation_constatee_id'] ?? null,
                        'service_constate_id' => $payload['service_constate_id'] ?? null,
                        'etat_constate' => $payload['etat_constate'] ?? null,
                        'commentaire_inventoriste' => $payload['commentaire_inventoriste'] ?? null,
                        'photos_inventaire' => $payload['photos_inventaire'] ?? null,
                        'gps_lat' => $payload['gps_lat'] ?? null,
                        'gps_lng' => $payload['gps_lng'] ?? null,
                        'inventorie_par_user_id' => $userId,
                        'inventorie_at' => $payload['inventorie_at'],
                        'synchronise_le' => now(),
                        'version' => $fiche->version + 1,
                    ]);

                    $accepted++;
                    continue;
                }

                // Cas B : fiche de découverte (bien non listé sur le périmètre)
                $immoId = $payload['immobilisation_id'] ?? null;
                if (! $immoId && ! empty($payload['code_scanne'])) {
                    $immoId = Immobilisation::where('code_inventaire', $payload['code_scanne'])->value('id');
                }

                $nouvelle = FicheInventaire::create([
                    'campagne_id' => $data['campagne_id'],
                    'immobilisation_id' => $immoId,
                    'code_attendu' => $payload['code_scanne'] ?? $payload['code_attendu'] ?? null,
                    'statut' => $payload['statut'] === 'decouverte' ? 'decouverte' : $payload['statut'],
                    'localisation_constatee_id' => $payload['localisation_constatee_id'] ?? null,
                    'service_constate_id' => $payload['service_constate_id'] ?? null,
                    'etat_constate' => $payload['etat_constate'] ?? null,
                    'commentaire_inventoriste' => $payload['commentaire_inventoriste'] ?? null,
                    'photos_inventaire' => $payload['photos_inventaire'] ?? null,
                    'gps_lat' => $payload['gps_lat'] ?? null,
                    'gps_lng' => $payload['gps_lng'] ?? null,
                    'inventorie_par_user_id' => $userId,
                    'inventorie_at' => $payload['inventorie_at'],
                    'synchronise_le' => now(),
                    'version' => 1,
                ]);

                $created[] = $nouvelle->id;
                $accepted++;
            }
        });

        $sync->update([
            'completed_at' => now(),
            'items_envoyes' => $accepted,
            'conflits' => $conflicts ?: null,
            'statut' => empty($conflicts) ? 'reussi' : 'partiel',
        ]);

        return response()->json([
            'data' => [
                'accepted' => $accepted,
                'conflicts' => $conflicts,
                'created_ids' => $created,
                'sync_id' => $sync->id,
            ],
        ]);
    }

    /**
     * SCANNER — endpoint léger : à partir d'un code scanné, renvoie la fiche
     * (ou la création d'une fiche découverte si le bien existe sans fiche).
     */
    public function scanner(Request $request, CampagneInventaire $campagne): JsonResponse
    {
        $data = $request->validate([
            'code_inventaire' => ['required', 'string'],
        ]);

        $immo = Immobilisation::where('code_inventaire', $data['code_inventaire'])->first();

        $fiche = $immo
            ? FicheInventaire::where('campagne_id', $campagne->id)
                ->where('immobilisation_id', $immo->id)
                ->first()
            : null;

        return response()->json([
            'data' => [
                'immobilisation_trouvee' => (bool) $immo,
                'fiche_existante' => $fiche ? new FicheInventaireResource($fiche->load('immobilisation')) : null,
                'immobilisation' => $immo ? new \App\Http\Resources\ImmobilisationResource($immo) : null,
                'action_suggeree' => $fiche ? 'inventorier' : ($immo ? 'creer_fiche_decouverte' : 'code_inconnu'),
            ],
        ]);
    }
}
