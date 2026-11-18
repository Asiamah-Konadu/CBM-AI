# Custo.AI

Custo.AI is a local commerce discovery prototype with a chat-first buyer flow, public seller profiles, and seller onboarding.

## Current UI prototype

The root `index.html` is a static chat demo. Search matches a small in-memory catalog from `catalog.js`; business cards open `profile.html`. `seller.html` demonstrates the onboarding flow. These pages are not connected to an API, account system, database, file storage, or live seller contacts.

## Laravel backend scaffold

The Laravel 13 application lives in [`server/`](server/). It includes PostgreSQL/PostGIS migrations, Eloquent models and relationships, role and owner policies, session auth endpoints, seller business/listing CRUD, and a public seller-profile endpoint. PostgreSQL full-text indexing is included; vector storage is deferred until an embedding provider/model is selected.

The JSON endpoints are defined in `server/routes/web.php` under `/api/v1`. They use Laravel's web session and CSRF middleware. Fetch clients should first request `/api/v1/csrf-token`, then send the returned token in the `X-CSRF-TOKEN` header with same-origin credentials.

The migration has not been applied. This machine has PHP 8.3 and Composer, but I did not find a Laragon PostgreSQL install directory or `psql` in `PATH`. Configure PostgreSQL/PostGIS, set the `DB_*` values in `server/.env` to match `server/.env.example`, create the `custo_ai` database, then run migrations and category seeds from `server/`:

```powershell
php artisan migrate
php artisan db:seed
```

Authentication supports buyer/seller registration, login, logout, and current-user lookup. Seller routes require a seller role and enforce business ownership through policies. Public profiles return only published businesses and listings. Reviewer policies are in place; evidence upload and review decision endpoints remain a later step.

The detailed proposed architecture and market assumptions are in [ARCHITECTURE.md](ARCHITECTURE.md). The SQL reference draft is in [`database/schema.sql`](database/schema.sql).

## Current assumptions

- First market: Accra, Ghana; default currency GHS.
- Sellers maintain availability; Custo records when it was last confirmed.
- Verification is manually reviewed; only approved businesses receive a badge.
- Buyers contact sellers directly. Checkout and payment processing are out of scope.
