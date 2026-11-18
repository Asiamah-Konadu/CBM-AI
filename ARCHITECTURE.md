# Custo.AI architecture draft

Status: accepted foundation draft. A Laravel 13 application and the first PostgreSQL/PostGIS migration now live under `server/`. The static prototype remains at the workspace root; no database is connected and the migration has not been applied.

## Product defaults for the first market

- Launch geography: Accra, Ghana. Store country and currency on each business/listing so the system can expand without assuming every seller uses one currency. Default display currency: GHS.
- Inventory: each seller is responsible for keeping listing availability current. Use availability states (`in_stock`, `limited`, `made_to_order`, `out_of_stock`, `unknown`) and record `last_confirmed_at`; do not imply live stock synchronization.
- Verification: manual Custo review of submitted business evidence. Only a reviewer can set a business to `verified`; submissions can be approved, rejected, or returned for changes. Keep an expiry/review date so the policy can require renewal.
- Discovery: first ship structured filters and PostgreSQL full-text search. Add embeddings after choosing an embedding provider/model and dimension. Treat the LLM as a query parser; all returned claims must come from current database fields.
- Contact: buyers contact sellers directly. No checkout or payment processing in this phase.

## Proposed stack

- **Application:** Laravel monolith with server-rendered public seller profiles and authenticated seller/reviewer areas. Keep the chat UI as a frontend module that calls the search endpoint.
- **Database:** PostgreSQL with PostGIS for radius/distance filtering. Use PostgreSQL full-text search for the first search release; add `pgvector` only after selecting an embedding model. Laravel supports PostgreSQL; PostGIS geography distance predicates can use spatial indexes, and PostgreSQL provides indexed full-text search. See [Laravel database support](https://laravel.com/docs/database), [PostGIS ST_DWithin](https://postgis.net/docs/ST_DWithin.html), [PostgreSQL full-text search](https://www.postgresql.org/docs/current/textsearch.html), and [pgvector hybrid search notes](https://github.com/pgvector/pgvector).
- **File storage:** private object storage for verification documents; public business/product images use a separate public bucket or CDN path. Never serve submitted verification evidence from a public profile URL.
- **LLM boundary:** provider-agnostic `SearchIntent` JSON with product/service, location, budget, quantity, condition, and availability. Validate and normalize values server-side before search. The database remains the source of truth.

## Main records

`users` and `roles` identify buyers, sellers, reviewers, and admins. A `business` belongs to an owner and has public profile data, contact information, location, publication state, and verification state. `listings` are products or services owned by a business. `media` stores file metadata and storage keys; verification documents are private and linked to review submissions. `categories` classify businesses and listings.

The reviewable PostgreSQL schema is in [`database/schema.sql`](database/schema.sql); the Laravel migration implementation is in [`server/database/migrations`](server/database/migrations). It includes PostGIS coordinates and full-text indexes. Vector columns are intentionally deferred until an embedding model is selected.

## API boundaries

- `GET /api/v1/csrf-token`, `POST /api/v1/auth/register`, `POST /api/v1/auth/login`, `POST /api/v1/auth/logout`, and `GET /api/v1/auth/me` — same-origin, session-based authentication. Seller registration also receives the buyer role.
- `GET /api/v1/categories` — active business and listing categories for seller forms.
- `POST /api/v1/search` — accepts natural language plus explicit location/filters; returns parsed intent and matching published listings/businesses.
- `GET /api/v1/businesses/{slug}` — public seller profile data backed by published business records. A server-rendered HTML profile route can wrap this data in the next UI integration step.
- `POST /api/v1/seller/businesses` and `PATCH /api/v1/seller/businesses/{id}` — seller-owned profile management.
- `POST /api/v1/seller/businesses/{id}/publish` / `unpublish` — seller-controlled listing visibility; publication does not grant a verification badge.
- `POST /api/v1/seller/businesses/{id}/listings` and `PATCH /api/v1/seller/listings/{id}` — seller-owned catalog management and availability confirmation.
- `POST /api/v1/seller/businesses/{id}/verification-submissions` — private evidence submission.
- `POST /api/v1/reviewer/verification-submissions/{id}/decision` — reviewer-only approval, rejection, or change request.

Authorization is checked on every seller-owned record using role middleware and model policies. Public endpoints expose only published business/listing fields and never expose private document paths or reviewer notes. Authentication uses Laravel's session guard and web CSRF middleware; the `/csrf-token` response supplies the header token for same-origin JSON requests.

## Delivery order

1. Configure a PostgreSQL/PostGIS development database, apply the migration, and seed categories.
2. Add private evidence upload and the reviewer decision workflow.
3. Replace the in-memory demo catalog with the search API and connect profile pages to canonical records.
4. Add the validated query parser and semantic retrieval after selecting an embedding provider/model.

## Open choices before production

- Exact verification evidence required for Ghanaian businesses, who can review it, and the renewal period.
- Whether listing prices are optional, quote-only, or expected for every listing; schema supports optional price and quote-only listings.
- File retention and deletion rules for rejected or expired verification submissions.
- Hosting/provider and the embedding model. These are intentionally not hard-coded in this draft.
