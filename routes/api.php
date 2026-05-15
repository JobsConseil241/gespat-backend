<?php

use App\Http\Controllers\Api\V1\AmortissementController;
use App\Http\Controllers\Api\V1\AuditController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CampagneInventaireController;
use App\Http\Controllers\Api\V1\CategorieController;
use App\Http\Controllers\Api\V1\ComptabiliteController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\EtiquetteController;
use App\Http\Controllers\Api\V1\FournisseurController;
use App\Http\Controllers\Api\V1\ImmobilisationController;
use App\Http\Controllers\Api\V1\ImmobilisationMediaController;
use App\Http\Controllers\Api\V1\LocalisationController;
use App\Http\Controllers\Api\V1\MaintenanceController;
use App\Http\Controllers\Api\V1\MobileSyncController;
use App\Http\Controllers\Api\V1\MouvementController;
use App\Http\Controllers\Api\V1\PlanCodificationController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\ServiceController;
use App\Http\Controllers\Api\V1\SiteController;
use App\Http\Controllers\Api\V1\SortieController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

    Route::middleware('auth:sanctum')->group(function () {
        // ===== AUTH =====
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('auth/me', [AuthController::class, 'me']);

        // ===== UTILISATEURS =====
        Route::apiResource('users', UserController::class)->parameters(['users' => 'user']);
        Route::post('users/{user}/password', [UserController::class, 'changePassword']);
        Route::post('users/{user}/roles', [UserController::class, 'assignRoles']);
        Route::get('roles', [UserController::class, 'roles']);

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

        // Médias
        Route::post('immobilisations/{immobilisation}/photos', [ImmobilisationMediaController::class, 'uploadPhoto']);
        Route::delete('immobilisations/{immobilisation}/photos/{photo}', [ImmobilisationMediaController::class, 'deletePhoto']);
        Route::post('immobilisations/{immobilisation}/documents', [ImmobilisationMediaController::class, 'uploadDocument']);
        Route::delete('immobilisations/{immobilisation}/documents/{document}', [ImmobilisationMediaController::class, 'deleteDocument']);

        // ===== MOUVEMENTS =====
        Route::get('mouvements', [MouvementController::class, 'index']);
        Route::post('mouvements', [MouvementController::class, 'store']);
        Route::get('mouvements/{mouvement}', [MouvementController::class, 'show']);
        Route::post('mouvements/{mouvement}/valider', [MouvementController::class, 'valider']);
        Route::post('mouvements/{mouvement}/refuser', [MouvementController::class, 'refuser']);

        // ===== SORTIES =====
        Route::get('sorties', [SortieController::class, 'index']);
        Route::post('sorties', [SortieController::class, 'store']);
        Route::get('sorties/{sortie}', [SortieController::class, 'show']);
        Route::post('sorties/{sortie}/valider', [SortieController::class, 'valider']);

        // ===== MAINTENANCES =====
        Route::apiResource('maintenances', MaintenanceController::class)
            ->parameters(['maintenances' => 'maintenance']);

        // ===== AMORTISSEMENTS =====
        Route::prefix('amortissements')->group(function () {
            Route::post('simuler', [AmortissementController::class, 'simuler']);
            Route::post('calculer-tous', [AmortissementController::class, 'calculerTous']);
            Route::post('calculer/{immobilisation}', [AmortissementController::class, 'calculer']);
            Route::get('immobilisation/{immobilisation}', [AmortissementController::class, 'showByImmobilisation']);
            Route::post('valider', [AmortissementController::class, 'valider']);
            Route::get('etat/{exercice}', [AmortissementController::class, 'etatExercice']);
        });

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

        // ===== RAPPROCHEMENT COMPTABLE =====
        Route::prefix('comptabilite')->group(function () {
            Route::post('imports', [ComptabiliteController::class, 'storeImport']);
            Route::get('imports/{import}/lignes', [ComptabiliteController::class, 'lignes']);
            Route::post('imports/{import}/matcher', [ComptabiliteController::class, 'executerMatching']);
            Route::post('lignes/{ligne}/matcher', [ComptabiliteController::class, 'matcherManuel']);
            Route::get('ecarts', [ComptabiliteController::class, 'ecarts']);
            Route::post('reconciliation', [ComptabiliteController::class, 'genererReconciliation']);
            Route::get('reconciliation/{exercice}', [ComptabiliteController::class, 'reconciliationExercice']);
        });

        // ===== DASHBOARD & REPORTING =====
        Route::prefix('dashboard')->group(function () {
            Route::get('kpi', [DashboardController::class, 'kpi']);
            Route::get('patrimoine-par-categorie', [DashboardController::class, 'patrimoineParCategorie']);
            Route::get('patrimoine-par-site', [DashboardController::class, 'patrimoineParSite']);
            Route::get('patrimoine-par-statut', [DashboardController::class, 'patrimoineParStatut']);
        });

        Route::prefix('reports')->group(function () {
            Route::get('immobilisations/excel', [ReportController::class, 'exportImmobilisationsExcel']);
            Route::get('immobilisations/pdf', [ReportController::class, 'etatPatrimoinePdf']);
        });

        // ===== AUDIT TRAIL =====
        Route::prefix('audit')->group(function () {
            Route::get('logs', [AuditController::class, 'index']);
            Route::get('stats', [AuditController::class, 'stats']);
        });
    });
});
