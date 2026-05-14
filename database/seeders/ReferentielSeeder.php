<?php

namespace Database\Seeders;

use App\Models\CategorieImmobilisation;
use App\Models\Localisation;
use App\Models\Service;
use App\Models\Site;
use Illuminate\Database\Seeder;

class ReferentielSeeder extends Seeder
{
    public function run(): void
    {
        // Sites (10+ sites du commanditaire — exemples Gabon)
        $sites = [
            ['code' => 'SIEGE', 'libelle' => 'Siège social', 'type' => 'siege', 'ville' => 'Libreville', 'province' => 'Estuaire', 'longitude' => 9.4536, 'latitude' => 0.4162],
            ['code' => 'AGC-LBV', 'libelle' => 'Agence Libreville', 'type' => 'agence', 'ville' => 'Libreville', 'province' => 'Estuaire', 'longitude' => 9.4673, 'latitude' => 0.4078],
            ['code' => 'AGC-PG', 'libelle' => 'Agence Port-Gentil', 'type' => 'agence', 'ville' => 'Port-Gentil', 'province' => 'Ogooué-Maritime', 'longitude' => 8.7815, 'latitude' => -0.7193],
            ['code' => 'AGC-FCV', 'libelle' => 'Agence Franceville', 'type' => 'agence', 'ville' => 'Franceville', 'province' => 'Haut-Ogooué', 'longitude' => 13.5828, 'latitude' => -1.6332],
            ['code' => 'AGC-OYM', 'libelle' => 'Agence Oyem', 'type' => 'agence', 'ville' => 'Oyem', 'province' => 'Woleu-Ntem', 'longitude' => 11.5793, 'latitude' => 1.6020],
            ['code' => 'AGC-LMB', 'libelle' => 'Agence Lambaréné', 'type' => 'agence', 'ville' => 'Lambaréné', 'province' => 'Moyen-Ogooué', 'longitude' => 10.2317, 'latitude' => -0.7000],
            ['code' => 'AGC-MKK', 'libelle' => 'Agence Makokou', 'type' => 'agence', 'ville' => 'Makokou', 'province' => 'Ogooué-Ivindo', 'longitude' => 12.8636, 'latitude' => 0.5739],
            ['code' => 'AGC-MND', 'libelle' => 'Agence Mouila', 'type' => 'agence', 'ville' => 'Mouila', 'province' => 'Ngounié', 'longitude' => 11.0556, 'latitude' => -1.8667],
            ['code' => 'AGC-TCH', 'libelle' => 'Agence Tchibanga', 'type' => 'agence', 'ville' => 'Tchibanga', 'province' => 'Nyanga', 'longitude' => 11.0254, 'latitude' => -2.8500],
            ['code' => 'DPT-CTR', 'libelle' => 'Dépôt Central', 'type' => 'depot', 'ville' => 'Libreville', 'province' => 'Estuaire', 'longitude' => 9.4400, 'latitude' => 0.4200],
        ];

        foreach ($sites as $data) {
            Site::firstOrCreate(['code' => $data['code']], $data);
        }

        // Localisations par défaut pour le siège
        $siege = Site::where('code', 'SIEGE')->first();
        if ($siege) {
            $batA = Localisation::firstOrCreate(
                ['site_id' => $siege->id, 'code' => 'BAT-A'],
                ['libelle' => 'Bâtiment A', 'type' => 'batiment']
            );
            $etg1 = Localisation::firstOrCreate(
                ['site_id' => $siege->id, 'code' => 'BAT-A-E1'],
                ['libelle' => 'Étage 1', 'type' => 'etage', 'parent_id' => $batA->id]
            );
            Localisation::firstOrCreate(
                ['site_id' => $siege->id, 'code' => 'BAT-A-E1-101'],
                ['libelle' => 'Bureau 101 — Direction', 'type' => 'bureau', 'parent_id' => $etg1->id]
            );
            Localisation::firstOrCreate(
                ['site_id' => $siege->id, 'code' => 'BAT-A-E1-102'],
                ['libelle' => 'Bureau 102 — Secrétariat', 'type' => 'bureau', 'parent_id' => $etg1->id]
            );
        }

        // Services
        $direction = Service::firstOrCreate(
            ['code' => 'DG'],
            ['libelle' => 'Direction Générale', 'site_principal_id' => $siege?->id]
        );
        Service::firstOrCreate(
            ['code' => 'DAF'],
            ['libelle' => 'Direction Administrative et Financière', 'parent_id' => $direction->id, 'site_principal_id' => $siege?->id]
        );
        Service::firstOrCreate(
            ['code' => 'DSI'],
            ['libelle' => 'Direction des Systèmes d\'Information', 'parent_id' => $direction->id, 'site_principal_id' => $siege?->id]
        );
        Service::firstOrCreate(
            ['code' => 'DP'],
            ['libelle' => 'Direction du Patrimoine', 'parent_id' => $direction->id, 'site_principal_id' => $siege?->id]
        );
        Service::firstOrCreate(
            ['code' => 'DRH'],
            ['libelle' => 'Direction des Ressources Humaines', 'parent_id' => $direction->id, 'site_principal_id' => $siege?->id]
        );

        // Catégories d'immobilisations SYSCOHADA
        $categories = [
            ['code' => 'BAT', 'libelle' => 'Bâtiments', 'classe_comptable' => '231', 'duree_amortissement_defaut' => 240, 'methode_amortissement_defaut' => 'lineaire'],
            ['code' => 'AGE', 'libelle' => 'Agencements et aménagements', 'classe_comptable' => '235', 'duree_amortissement_defaut' => 120, 'methode_amortissement_defaut' => 'lineaire'],
            ['code' => 'MOB', 'libelle' => 'Mobilier de bureau', 'classe_comptable' => '244', 'duree_amortissement_defaut' => 120, 'methode_amortissement_defaut' => 'lineaire'],
            ['code' => 'MAT-INF', 'libelle' => 'Matériel informatique', 'classe_comptable' => '2442', 'duree_amortissement_defaut' => 36, 'methode_amortissement_defaut' => 'lineaire'],
            ['code' => 'LOG', 'libelle' => 'Logiciels', 'classe_comptable' => '2131', 'duree_amortissement_defaut' => 36, 'methode_amortissement_defaut' => 'lineaire'],
            ['code' => 'MAT-BUR', 'libelle' => 'Matériel de bureau', 'classe_comptable' => '2441', 'duree_amortissement_defaut' => 60, 'methode_amortissement_defaut' => 'lineaire'],
            ['code' => 'VEH-LEG', 'libelle' => 'Véhicules légers', 'classe_comptable' => '2451', 'duree_amortissement_defaut' => 48, 'methode_amortissement_defaut' => 'lineaire'],
            ['code' => 'VEH-UTI', 'libelle' => 'Véhicules utilitaires', 'classe_comptable' => '2452', 'duree_amortissement_defaut' => 60, 'methode_amortissement_defaut' => 'lineaire'],
            ['code' => 'MAT-OUT', 'libelle' => 'Matériel et outillage', 'classe_comptable' => '241', 'duree_amortissement_defaut' => 84, 'methode_amortissement_defaut' => 'lineaire'],
            ['code' => 'TER', 'libelle' => 'Terrains', 'classe_comptable' => '22', 'duree_amortissement_defaut' => null],
        ];

        foreach ($categories as $cat) {
            CategorieImmobilisation::firstOrCreate(['code' => $cat['code']], $cat);
        }
    }
}
