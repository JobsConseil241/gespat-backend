<?php

namespace Database\Seeders;

use App\Models\PlanCodification;
use Illuminate\Database\Seeder;

class CodificationSeeder extends Seeder
{
    public function run(): void
    {
        PlanCodification::firstOrCreate(
            ['code' => 'PLAN-DEFAUT'],
            [
                'libelle' => 'Plan de codification par défaut',
                'description' => 'Format : ORG-CATEGORIE-SITE-ANNEE-SEQ (séquentiel annuel global)',
                'format' => '{ORG}-{CAT}-{SITE}-{ANNEE}-{SEQ:05d}',
                'separateur' => '-',
                'mode_sequentiel' => 'annuel',
                'actif' => true,
            ]
        );
    }
}
