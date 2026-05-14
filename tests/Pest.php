<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

pest()->extend(TestCase::class)->in('Unit');

function actingAsAdmin(): \App\Models\User
{
    $user = createUserWithRole('super_admin', 'admin-test@gespat.local');
    test()->actingAs($user, 'sanctum');

    return $user;
}

function actingAsRole(string $role, string $email = null): \App\Models\User
{
    $user = createUserWithRole($role, $email ?? "{$role}@gespat.local");
    test()->actingAs($user, 'sanctum');

    return $user;
}

function createUserWithRole(string $role, string $email): \App\Models\User
{
    /** @var \App\Models\User $user */
    $user = \App\Models\User::factory()->create([
        'email' => $email,
        'matricule' => strtoupper(substr(md5($email), 0, 8)),
    ]);
    $user->assignRole($role);

    return $user;
}
