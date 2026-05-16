# Authenticating requests

To authenticate requests, include an **`Authorization`** header with the value **`"Bearer {YOUR_BEARER_TOKEN}"`**.

All authenticated endpoints are marked with a `requires authentication` badge in the documentation below.

Récupérer un token via `POST /api/v1/auth/login` avec email + password. Inclure ensuite l'en-tête `Authorization: Bearer <token>` dans chaque requête.
