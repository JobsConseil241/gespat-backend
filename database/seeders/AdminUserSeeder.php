<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\Site;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $siege = Site::where('code', 'SIEGE')->first();
        $dsi = Service::where('code', 'DSI')->first();
        $dp = Service::where('code', 'DP')->first();

        $admin = User::firstOrCreate(
            ['email' => 'admin@gespat.local'],
            [
                'matricule' => 'ADM-0001',
                'nom' => 'Administrateur',
                'prenom' => 'Système',
                'password' => Hash::make('ChangeMe!2026'),
                'site_id' => $siege?->id,
                'service_id' => $dsi?->id,
                'fonction' => 'Administrateur de la plateforme',
                'is_active' => true,
            ]
        );
        $admin->syncRoles(['super_admin']);

        $patrimoine = User::firstOrCreate(
            ['email' => 'patrimoine@gespat.local'],
            [
                'matricule' => 'DP-0001',
                'nom' => 'Demo',
                'prenom' => 'Patrimoine',
                'password' => Hash::make('ChangeMe!2026'),
                'site_id' => $siege?->id,
                'service_id' => $dp?->id,
                'fonction' => 'Gestionnaire patrimoine',
                'is_active' => true,
            ]
        );
        $patrimoine->syncRoles(['gestionnaire_patrimoine']);
    }
}
