# Introduction

API REST de la plateforme GESPAT (Gestion du Patrimoine & Inventaire). Couvre la gestion des immobilisations, l'inventaire physique mobile, la codification SYSCOHADA, le rapprochement comptable et le reporting.

<aside>
    <strong>Base URL</strong>: <code>http://localhost:8000</code>
</aside>

    Cette API expose toutes les fonctionnalités de la plateforme GESPAT (Phase 1 + Phase 2 du spec).

    **Versions et authentification**
    - Préfixe : `/api/v1`
    - Authentification : `Bearer token` via Laravel Sanctum
    - Récupérer un token : `POST /api/v1/auth/login` avec email + password

    **Modules disponibles**
    - Authentification & Administration (utilisateurs, rôles, audit)
    - Référentiel (sites, localisations, services, catégories SYSCOHADA, fournisseurs)
    - Immobilisations (CRUD + photos + documents)
    - Cycle de vie (mouvements, sorties, maintenances, amortissements)
    - Inventaire (campagnes, fiches, sync mobile)
    - Codification & étiquettes (plans, lots PDF)
    - Rapprochement comptable (import, matching, réconciliation)
    - Dashboard & reporting (KPI, exports Excel/PDF)

    <aside>Les exemples à droite sont disponibles en bash et JavaScript. Utilisez les onglets en haut pour basculer entre les langages.</aside>

