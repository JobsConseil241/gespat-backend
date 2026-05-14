<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CategorieController;
use App\Http\Controllers\Api\V1\FournisseurController;
use App\Http\Controllers\Api\V1\LocalisationController;
use App\Http\Controllers\Api\V1\ServiceController;
use App\Http\Controllers\Api\V1\SiteController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:10,1');

    Route::middleware('auth:sanctum')->group(function () {
        // Auth
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('auth/me', [AuthController::class, 'me']);

        // Référentiel
        Route::apiResource('sites', SiteController::class)
            ->parameters(['sites' => 'site']);
        Route::get('sites/{site}/localisations', [SiteController::class, 'localisations']);

        Route::apiResource('localisations', LocalisationController::class)
            ->parameters(['localisations' => 'localisation']);

        Route::apiResource('services', ServiceController::class)
            ->parameters(['services' => 'service']);

        Route::apiResource('categories', CategorieController::class)
            ->parameters(['categories' => 'categorie']);

        Route::apiResource('fournisseurs', FournisseurController::class)
            ->parameters(['fournisseurs' => 'fournisseur']);
    });
});
