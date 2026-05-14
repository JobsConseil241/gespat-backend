<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Référentiel
            'sites.view', 'sites.create', 'sites.update', 'sites.delete',
            'localisations.view', 'localisations.create', 'localisations.update', 'localisations.delete',
            'services.view', 'services.create', 'services.update', 'services.delete',
            'categories.view', 'categories.create', 'categories.update', 'categories.delete',
            'fournisseurs.view', 'fournisseurs.create', 'fournisseurs.update', 'fournisseurs.delete',
            'marques.view', 'marques.create', 'marques.update', 'marques.delete',
            // Immobilisations
            'immobilisations.view', 'immobilisations.create', 'immobilisations.update', 'immobilisations.delete',
            'immobilisations.transfer', 'immobilisations.sortie',
            // Inventaire
            'campagnes.view', 'campagnes.create', 'campagnes.update', 'campagnes.cloturer',
            'fiches.scanner', 'fiches.valider',
            // Comptabilité
            'compta.import', 'compta.matcher', 'compta.reconcilier',
            'amortissements.calculer', 'amortissements.valider',
            // Étiquettes
            'etiquettes.generer', 'etiquettes.imprimer',
            // Administration
            'users.view', 'users.create', 'users.update', 'users.delete',
            'roles.manage', 'audit.view', 'system.manage',
            // Reporting
            'reports.view', 'reports.export',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        $roles = [
            'super_admin' => $permissions,
            'admin_patrimoine' => array_values(array_filter($permissions, fn ($p) => ! str_starts_with($p, 'system.') && $p !== 'roles.manage')),
            'gestionnaire_patrimoine' => [
                'sites.view', 'localisations.view', 'localisations.create', 'localisations.update',
                'services.view', 'categories.view', 'fournisseurs.view', 'fournisseurs.create', 'fournisseurs.update',
                'marques.view', 'marques.create',
                'immobilisations.view', 'immobilisations.create', 'immobilisations.update', 'immobilisations.transfer',
                'campagnes.view', 'campagnes.create', 'campagnes.update',
                'fiches.valider', 'etiquettes.generer', 'etiquettes.imprimer',
                'reports.view', 'reports.export',
            ],
            'inventoriste' => [
                'sites.view', 'localisations.view', 'services.view', 'categories.view',
                'immobilisations.view', 'campagnes.view', 'fiches.scanner',
            ],
            'comptable' => [
                'sites.view', 'localisations.view', 'services.view', 'categories.view',
                'fournisseurs.view', 'immobilisations.view',
                'compta.import', 'compta.matcher', 'compta.reconcilier',
                'amortissements.calculer', 'amortissements.valider',
                'reports.view', 'reports.export',
            ],
            'auditeur_lecture' => array_values(array_filter($permissions, fn ($p) => str_ends_with($p, '.view') || $p === 'audit.view' || $p === 'reports.view')),
            'chef_service' => [
                'sites.view', 'localisations.view', 'services.view', 'categories.view',
                'immobilisations.view', 'immobilisations.transfer',
                'reports.view',
            ],
            'agent_simple' => [
                'immobilisations.view',
            ],
        ];

        foreach ($roles as $name => $perms) {
            $role = Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
            $role->syncPermissions($perms);
        }
    }
}
