<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UserController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:users.view', only: ['index', 'show']),
            new Middleware('can:users.create', only: ['store']),
            new Middleware('can:users.update', only: ['update', 'changePassword', 'assignRoles']),
            new Middleware('can:users.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = User::query()
            ->with(['site:id,code,libelle', 'service:id,code,libelle', 'roles:id,name'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->string('search').'%';
                $q->where(function ($q) use ($term) {
                    $q->where('matricule', 'ilike', $term)
                        ->orWhere('nom', 'ilike', $term)
                        ->orWhere('prenom', 'ilike', $term)
                        ->orWhere('email', 'ilike', $term);
                });
            })
            ->when($request->filled('site_id'), fn ($q) => $q->where('site_id', $request->integer('site_id')))
            ->when($request->filled('service_id'), fn ($q) => $q->where('service_id', $request->integer('service_id')))
            ->when($request->filled('role'), fn ($q) => $q->whereHas('roles', fn ($q) => $q->where('name', $request->string('role'))))
            ->when($request->filled('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->orderBy('matricule');

        return UserResource::collection($query->paginate($request->integer('per_page', 25)));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'matricule' => ['required', 'string', 'max:50', 'unique:users,matricule'],
            'nom' => ['required', 'string', 'max:100'],
            'prenom' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'unique:users,email'],
            'telephone' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'string', 'min:8'],
            'site_id' => ['nullable', 'integer', 'exists:sites,id'],
            'service_id' => ['nullable', 'integer', 'exists:services,id'],
            'fonction' => ['nullable', 'string', 'max:150'],
            'is_active' => ['boolean'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', 'exists:roles,name'],
        ]);

        $roles = $data['roles'] ?? [];
        unset($data['roles']);
        $data['password'] = Hash::make($data['password']);

        $user = User::create($data);
        if (! empty($roles)) {
            $user->syncRoles($roles);
        }

        return response()->json(['data' => new UserResource($user->load(['site', 'service', 'roles']))], 201);
    }

    public function show(User $user): UserResource
    {
        return new UserResource($user->load(['site', 'service', 'roles', 'permissions']));
    }

    public function update(Request $request, User $user): UserResource
    {
        $data = $request->validate([
            'nom' => ['sometimes', 'string', 'max:100'],
            'prenom' => ['sometimes', 'string', 'max:100'],
            'email' => ['sometimes', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'telephone' => ['nullable', 'string', 'max:30'],
            'site_id' => ['nullable', 'integer', 'exists:sites,id'],
            'service_id' => ['nullable', 'integer', 'exists:services,id'],
            'fonction' => ['nullable', 'string', 'max:150'],
            'is_active' => ['boolean'],
        ]);

        $user->update($data);

        return new UserResource($user->fresh(['site', 'service', 'roles']));
    }

    public function changePassword(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'password' => ['required', 'string', 'min:8'],
        ]);
        $user->update(['password' => Hash::make($data['password'])]);

        return response()->json(['message' => 'Mot de passe mis à jour.']);
    }

    public function assignRoles(Request $request, User $user): UserResource
    {
        $data = $request->validate([
            'roles' => ['required', 'array'],
            'roles.*' => ['string', 'exists:roles,name'],
        ]);
        $user->syncRoles($data['roles']);

        return new UserResource($user->fresh(['site', 'service', 'roles']));
    }

    public function destroy(User $user): JsonResponse
    {
        abort_if($user->id === auth()->id(), 422, 'Vous ne pouvez pas supprimer votre propre compte.');
        $user->delete();

        return response()->json(['message' => 'Utilisateur désactivé (soft delete).']);
    }

    public function roles(): JsonResponse
    {
        return response()->json(['data' => Role::all(['id', 'name'])]);
    }
}
