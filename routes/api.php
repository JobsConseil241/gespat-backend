<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CampagneInventaireController;
use App\Http\Controllers\Api\V1\CategorieController;
use App\Http\Controllers\Api\V1\EtiquetteController;
use App\Http\Controllers\Api\V1\FournisseurController;
use App\Http\Controllers\Api\V1\ImmobilisationController;
use App\Http\Controllers\Api\V1\LocalisationController;
use App\Http\Controllers\Api\V1\MobileSyncController;
use App\Http\Controllers\Api\V1\PlanCodificationController;
use App\Http\Controllers\Api\V1\ServiceController;
use App\Http\Controllers\Api\V1\SiteController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:10,1');

    Route::middleware('auth:sanctum')->group(function () {
        // ===== AUTH =====
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('auth/me', [AuthController::class, 'me']);

        // ===== RÉFÉRENTIEL =====
        Route::apiResource('sites', SiteController::class)->parameters(['sites' => 'site']);
        Route::get('sites/{site}/localisations', [SiteController::class, 'localisations']);
        Route::apiResource('localisations', LocalisationController::class)->parameters(['localisations' => 'localisation']);
        Route::apiResource('services', ServiceController::class)->parameters(['services' => 'service']);
        Route::apiResource('categories', CategorieController::class)->parameters(['categories' => 'categorie']);
        Route::apiResource('fournisseurs', FournisseurController::class)->parameters(['fournisseurs' => 'fournisseur']);

        // ===== IMMOBILISATIONS =====
        Route::apiResource('immobilisations', ImmobilisationController::class)
            ->parameters(['immobilisations' => 'immobilisation']);
        Route::get('immobilisations/by-code/{code}', [ImmobilisationController::class, 'byCode']);

        // ===== CODIFICATION =====
        Route::prefix('codification')->group(function () {
            Route::get('plans', [PlanCodificationController::class, 'index']);
            Route::post('plans', [PlanCodificationController::class, 'store']);
            Route::post('plans/{plan}/activer', [PlanCodificationController::class, 'activer']);
            Route::post('previsualiser', [PlanCodificationController::class, 'previsualiser']);
        });

        // ===== ÉTIQUETTES =====
        Route::prefix('etiquettes')->group(function () {
            Route::get('lots', [EtiquetteController::class, 'listLots']);
            Route::post('lots', [EtiquetteController::class, 'createLot']);
            Route::get('lots/{lot}/pdf', [EtiquetteController::class, 'genererPdf']);
            Route::post('lots/{lot}/imprime', [EtiquetteController::class, 'marquerImprime']);
            Route::post('{etiquette}/reediter', [EtiquetteController::class, 'reediter']);
        });

        // ===== CAMPAGNES D'INVENTAIRE =====
        Route::apiResource('campagnes', CampagneInventaireController::class)
            ->parameters(['campagnes' => 'campagne'])
            ->only(['index', 'store', 'show', 'update']);
        Route::post('campagnes/{campagne}/generer-fiches', [CampagneInventaireController::class, 'generateFiches']);
        Route::get('campagnes/{campagne}/avancement', [CampagneInventaireController::class, 'avancement']);
        Route::get('campagnes/{campagne}/fiches', [CampagneInventaireController::class, 'fiches']);
        Route::post('campagnes/{campagne}/cloturer', [CampagneInventaireController::class, 'cloturer']);

        // ===== MOBILE SYNC =====
        Route::prefix('sync')->group(function () {
            Route::get('campagnes/{campagne}/pull', [MobileSyncController::class, 'pull']);
            Route::post('campagnes/{campagne}/scanner', [MobileSyncController::class, 'scanner']);
            Route::post('push', [MobileSyncController::class, 'push']);
        });
    });
});
