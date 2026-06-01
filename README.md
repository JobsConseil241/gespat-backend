# GESPAT — Backend API

API REST de la plateforme **GESPAT** (Gestion du patrimoine mobilier et immobilier) — codification, inventaire mobile, immobilisations, amortissements SYSCOHADA, rapprochement comptable, reporting.

> **Maître d'œuvre** : MRTECH — MEBODO Richard Aristide
> **Stack** : Laravel 11 · PHP 8.3 · PostgreSQL 16 · Redis · Sanctum

## Vue d'ensemble

| Métrique | Valeur |
|---|---|
| Tables PostgreSQL | 45 |
| Routes API (`/api/v1/*`) | ~77 |
| Contrôleurs | 21 |
| Services métier | 4 |
| Tests Pest | **111** (317 assertions) |
| Couverture fonctionnelle | Phase 1 + Phase 2 du spec |

## Modules

| # | Module | Endpoints clés |
|---|---|---|
| 0 | **Administration** | `/users`, `/roles`, `/audit/logs`, `/audit/stats` |
| 1 | **Référentiel** | `/sites`, `/localisations`, `/services`, `/categories`, `/fournisseurs` |
| 2 | **Campagnes d'inventaire** | `/campagnes`, `/campagnes/{id}/generer-fiches`, `/sync/pull`, `/sync/push`, `/sync/scanner` |
| 3 | **Codification & étiquetage** | `/codification/plans`, `/codification/previsualiser`, `/etiquettes/lots`, `/etiquettes/lots/{id}/pdf` |
| 4 | **Immobilisations + cycle de vie** | `/immobilisations`, `/mouvements`, `/sorties`, `/maintenances` |
| 5 | **Rapprochement comptable** | `/comptabilite/imports`, `/comptabilite/imports/{id}/matcher`, `/comptabilite/reconciliation` |
| 6 | **Reporting** | `/dashboard/kpi`, `/dashboard/patrimoine-par-categorie/site/statut`, `/reports/immobilisations/excel,pdf` |
|   | **Amortissements SYSCOHADA** | `/amortissements/simuler`, `/calculer-tous`, `/valider`, `/etat/{exercice}` |

## Packages clés

- `laravel/sanctum` — auth API token
- `laravel/horizon` — queue dashboard Redis
- `spatie/laravel-permission` — RBAC (8 rôles, 52 permissions)
- `owen-it/laravel-auditing` — audit trail automatique sur tous les modèles métier
- `picqer/php-barcode-generator` — code-barres CODE128
- `endroid/qr-code` — QR codes signés HMAC
- `barryvdh/laravel-dompdf` — PDF (planches d'étiquettes A4 Avery, états patrimoine)
- `maatwebsite/excel` — import Excel comptable, export inventaire
- `knuckleswtf/scribe` — documentation OpenAPI 3.0 auto-générée

## Démarrage local

### Prérequis

- PHP 8.3+ (`brew install php@8.3` ou MAMP)
- Composer 2.x
- PostgreSQL 16+ (`brew install postgresql@16`)
- Redis (`brew install redis`)

### Installation

```bash
# 1. Cloner
git clone https://github.com/JobsConseil241/gespat-backend.git
cd gespat-backend

# 2. Dépendances
composer install

# 3. Configuration
cp .env.example .env
php artisan key:generate

# 4. Base de données
psql postgres -c "CREATE USER gespat WITH PASSWORD 'gespat';"
psql postgres -c "CREATE DATABASE gespat OWNER gespat;"
psql postgres -c "CREATE DATABASE gespat_test OWNER gespat;"
php artisan migrate --seed

# 5. Démarrer
php artisan serve --host=127.0.0.1 --port=8000
```

L'API est sur **http://127.0.0.1:8000/api/v1**.

### Comptes de démo (seedés)

```
admin@gespat.local      / ChangeMe!2026  (super_admin)
patrimoine@gespat.local / ChangeMe!2026  (gestionnaire_patrimoine)
```

⚠️ **À changer immédiatement en production.**

## Tests

```bash
./vendor/bin/pest                            # toute la suite
./vendor/bin/pest --filter=Amortissement     # un fichier
./vendor/bin/pest --coverage                 # avec couverture
```

13 fichiers de tests Feature couvrant chaque module :

```
Auth · Site · Fournisseur · Categorie
Immobilisation · MouvementSortie · Maintenance · Amortissement
Codification · Etiquette · Comptabilite
CampagneInventaire · MobileSync
Dashboard · Audit · UserManagement
```

## Architecture

```
app/
├── Http/
│   ├── Controllers/Api/V1/      # 21 contrôleurs RESTful
│   ├── Requests/                # Form requests par module
│   └── Resources/               # API Resources (sérialisation)
├── Models/                       # Eloquent + Auditable + SoftDeletes
├── Services/
│   ├── Amortissement/           # Calcul SYSCOHADA linéaire + dégressif
│   ├── Codification/            # Génération codes + QR/barcode
│   └── Comptabilite/            # Matching auto (3 stratégies)
├── Exports/                      # maatwebsite/excel
├── Imports/                      # Import écritures comptables
config/
├── permission.php                # Spatie config
├── audit.php                     # owen-it config
├── gespat.php                    # Paramètres métier (codification, HMAC…)
database/
├── migrations/                   # 30 migrations
├── seeders/                      # RoleSeeder, ReferentielSeeder, CodificationSeeder, AdminUserSeeder
├── factories/                    # Factories pour les tests
routes/
└── api.php                       # Toutes les routes sous /api/v1
```

## RBAC — 8 rôles seedés (52 permissions)

| Rôle | Périmètre |
|---|---|
| `super_admin` | tout |
| `admin_patrimoine` | tout sauf paramétrage système |
| `gestionnaire_patrimoine` | CRUD biens, campagnes, étiquettes, mouvements |
| `inventoriste` | scan terrain, saisie fiches, lecture |
| `comptable` | imports, matching, validation amortissements |
| `auditeur_lecture` | lecture seule + audit logs |
| `chef_service` | lecture biens du service, validation transferts |
| `agent_simple` | lecture biens sous responsabilité |

## Documentation API

Scribe expose la doc auto-générée :

```bash
php artisan scribe:generate
```

- HTML interactif : `http://127.0.0.1:8000/docs`
- OpenAPI 3.0 : `http://127.0.0.1:8000/docs.openapi`
- Collection Postman : `http://127.0.0.1:8000/docs.postman`

## Déploiement production

Voir `.env.example` pour les variables. Points d'attention :

- `APP_DEBUG=false` impératif
- `APP_KEY` régénéré (`php artisan key:generate`)
- `GESPAT_HMAC_SECRET` régénéré (`openssl rand -hex 32`)
- HTTPS obligatoire (Let's Encrypt ou certif commercial)
- `SANCTUM_STATEFUL_DOMAINS` ajusté au domaine frontend
- `config/cors.php` whitelist du frontend
- Throttle `/auth/login` : ajusté à `10,1` en prod (actuellement `30,1` pour la CI E2E)
- Cron Laravel Scheduler :
  ```
  * * * * * cd /path/to/backend && php artisan schedule:run >> /dev/null 2>&1
  ```
- `php artisan config:cache route:cache view:cache` après chaque déploiement
- Backup quotidien `pg_dump` + rétention ≥ 30 jours (obligation OHADA 10 ans)

## Surfaces liées

- Frontend Vue 3 : https://github.com/JobsConseil241/gespat-frontend
- Mobile Flutter : https://github.com/JobsConseil241/gespat-mobile (privé)

## Licence

Propriétaire — MRTECH / Commanditaire. Tous droits réservés.
