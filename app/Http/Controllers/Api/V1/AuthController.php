<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * @group Authentification
 *
 * Endpoints liés à la session utilisateur (login, logout, profil courant).
 */
class AuthController extends Controller
{
    /**
     * Connexion
     *
     * Authentifie un utilisateur et retourne un token Bearer Sanctum.
     *
     * @unauthenticated
     *
     * @response 200 {
     *   "token": "1|aBcDeFgHiJ...",
     *   "user": {"id": 1, "matricule": "ADM-0001", "email": "admin@gespat.local", "nom_complet": "Système Administrateur"}
     * }
     * @response 422 {
     *   "message": "The given data was invalid.",
     *   "errors": {"email": ["Identifiants invalides."]}
     * }
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $key = 'login:'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            throw ValidationException::withMessages([
                'email' => ["Trop de tentatives. Réessayez dans {$seconds} secondes."],
            ]);
        }

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Auth::attempt($request->only('email', 'password'))) {
            RateLimiter::hit($key, 900);
            throw ValidationException::withMessages([
                'email' => ['Identifiants invalides.'],
            ]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['Ce compte est désactivé.'],
            ]);
        }

        RateLimiter::clear($key);
        $user->update(['last_login_at' => now()]);

        $token = $user->createToken(
            name: $request->input('device_name', 'web'),
            abilities: ['*']
        );

        return response()->json([
            'token' => $token->plainTextToken,
            'user' => new UserResource($user->loadMissing(['site', 'service'])),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Déconnexion réussie.']);
    }

    public function me(Request $request): UserResource
    {
        return new UserResource(
            $request->user()->load(['site', 'service', 'roles', 'permissions'])
        );
    }
}
