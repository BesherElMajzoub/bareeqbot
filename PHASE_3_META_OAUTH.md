# Phase 3 — Meta OAuth Connect (Build Spec / AI Handoff)

> Implement **Phase 3** of Bariq: connect a tenant's Facebook Pages & Instagram business
> accounts via **Facebook Login for Business**, store their access tokens **encrypted**, and
> **subscribe each asset to webhooks**. This is a handoff spec for an implementing agent.
> **A separate reviewer will write the Pest tests after you finish — do NOT skip writing code
> that is testable, but you are not required to author the test suite yourself.**

## 0. Read first (non-negotiable)

- Follow **[CLAUDE.md](CLAUDE.md)** and **[BARIQ_BUILD_PROMPT.md](BARIQ_BUILD_PROMPT.md) §6.3 + §8**
  exactly. Do not invent product behavior not specified there — ask if unsure.
- Architecture rules still apply: **thin controllers → Action/Service → Resource**; Action classes
  for writes; Service classes for the Meta integration; **DTOs (`spatie/laravel-data`) for every Meta
  API response**; Policy + Form Request for every action; **all tokens `encrypted` at rest and NEVER
  logged**; idempotency where relevant.
- **Do not build the webhook event *processing* pipeline** (parsing changes → rule matching → replies).
  That is Phase 4. Phase 3 only: OAuth connect, encrypted token storage, quota enforcement, and
  **asset webhook *subscription*** + the minimal **GET webhook *verification*** endpoint (so the
  webhook can be registered in the Meta dashboard).

## 1. What already exists (build on it, don't duplicate)

- **Tenancy**: `App\Concerns\BelongsToTenant`, `App\Scopes\TenantScope`, `App\Support\TenantContext`,
  `SetCurrentTenant` middleware. New tenant-owned models MUST use `BelongsToTenant`.
- **Quota**: `App\Services\Billing\SubscriptionQuota` with `canConnectMore(Tenant)`, `maxPages`,
  `usedPages`. `usedPages` already counts a `channel_connections` table once it exists — so creating
  that table automatically activates quota accounting.
- **Roles**: tenant `owner`/`member` have `manage-connections` permission (Spatie, teams-enabled).
- **Config**: `config/services.php → services.meta` already exposes `graph_version`, `app_id`,
  `app_secret`, `webhook_verify_token`, `graph_base_url`. Read Meta settings **only** through
  `config('services.meta.*')` — never `env()` at runtime.
- **Route-binding caveat** (see CLAUDE.md): `SubstituteBindings` runs before `SetCurrentTenant`, so
  implicit route-model bindings are NOT tenant-scoped. For `{channelConnection}` routes, **authorize
  with a Policy** and resolve within the tenant (do not trust the ambient scope).

## 2. Database — `channel_connections`

Migration `create_channel_connections_table` (per BARIQ §5):

| column | type | notes |
|---|---|---|
| id | bigint pk | |
| tenant_id | fk → tenants, cascadeOnDelete | scoped |
| platform | string | `facebook` \| `instagram` (enum-backed) |
| provider_account_id | string | page id or IG user id |
| linked_page_id | string, nullable | for IG-via-page |
| name | string | |
| username | string, nullable | IG username |
| access_token | text | **`encrypted` cast** |
| token_expires_at | timestamp, nullable | |
| webhook_subscribed | boolean, default false | |
| status | string, default `active` | `active` \| `revoked` \| `error` |
| meta | json, nullable | raw extra fields |
| connected_by | fk → users, nullable | |
| timestamps | | |

**Unique (`platform`, `provider_account_id`)**. Index (`tenant_id`, `status`).

## 3. Enums

- `App\Enums\ChannelPlatform: string { Facebook = 'facebook'; Instagram = 'instagram'; }`
- `App\Enums\ChannelStatus: string { Active = 'active'; Revoked = 'revoked'; Error = 'error'; }`

## 4. Model — `App\Models\ChannelConnection`

- `use BelongsToTenant, HasFactory;`
- `casts`: `platform => ChannelPlatform::class`, `status => ChannelStatus::class`,
  **`access_token => 'encrypted'`**, `token_expires_at => 'datetime'`, `webhook_subscribed => 'boolean'`,
  `meta => 'array'`.
- `#[Hidden(['access_token'])]` so it never serializes to the frontend.
- Relations: `tenant()` (from trait), `connectedBy()` → User.
- Add `channelConnections(): HasMany` to `Tenant` (drop the tenant scope like the `subscriptions()`
  relation does).
- Factory `ChannelConnectionFactory` (+ `facebook()`, `instagram()`, `revoked()` states).

## 5. DTOs (`spatie/laravel-data`) — `app/Data/Meta/`

- `MetaUserTokenData` { access_token, token_type, expires_in }.
- `MetaPageData` { id, name, access_token, tasks?, instagram_business_account_id? }.
- `MetaInstagramAccountData` { id, username, name? }.

Hydrate these from Graph responses; pass DTOs across boundaries (never raw arrays).

## 6. Services — `app/Services/Meta/`

### 6.1 `MetaGraphClient`
Central HTTP client for all Graph calls. Requirements:
- Base URL `config('services.meta.graph_base_url')` + pinned `config('services.meta.graph_version')`.
- Token injection per call; **retry with backoff** on transient errors; **respect Meta rate-limit
  headers** (`X-App-Usage` / `X-Business-Use-Case-Usage`) and back off.
- Typed responses (return DTOs), **structured error handling** (throw a `MetaApiException` carrying
  Meta's error `code`/`type`/`fbtrace_id`). **Never log tokens or secrets.**
- Methods needed this phase: `exchangeCodeForToken(code, redirectUri)`,
  `exchangeForLongLivedToken(shortToken)`, `getUserPages(userToken)` (`/me/accounts` with fields
  `id,name,access_token,tasks,instagram_business_account{id,username}`),
  `subscribeAppToPage(pageId, pageToken, fields[])` (`POST /{page-id}/subscribed_apps`).

### 6.2 `FacebookOAuthService`
- `redirectUrl(string $state): string` — builds the Login-for-Business dialog URL with `client_id`,
  `redirect_uri`, `state`, and the **required scopes** (§8).
- `handleCallback(string $code, string $redirectUri): array{token: MetaUserTokenData, pages: Collection<MetaPageData>}`
  — exchange → long-lived → fetch pages/IG. Orchestrates `MetaGraphClient`.

## 7. Actions — `app/Actions/Connections/`

### 7.1 `ConnectChannel`
`handle(Tenant $tenant, MetaPageData $page, ChannelPlatform $platform, User $connectedBy, string $userLongLivedToken): ChannelConnection`
1. **Enforce quota**: `abort` / throw `QuotaExceededException` unless `SubscriptionQuota::canConnectMore($tenant)`.
2. Upsert `channel_connections` by (`platform`, `provider_account_id`) — reconnect updates the token/status.
3. Store the **Page Access Token** (for FB) or the IG-linked page token (for IG) — encrypted via cast.
4. **Subscribe the asset to webhooks** (`MetaGraphClient::subscribeAppToPage`), set
   `webhook_subscribed = true`. On failure, set `status = error` and surface the error (do not leave a
   half-connected asset silently).
5. Wrap in a DB transaction. Return the connection.

### 7.2 `DisconnectChannel`
`handle(ChannelConnection $connection): void` — mark `revoked` (and optionally call the Graph API to
remove the subscription). Frees quota.

> **Webhook subscription is two-level (BARIQ §8).** App-level (`POST /{APP_ID}/subscriptions` with
> `object=page`/`instagram`, `callback_url`, `verify_token`, fields) is configured **once** in the
> Meta App Dashboard (manual, see the setup checklist). Asset-level (`/{PAGE_ID}/subscribed_apps`) is
> done per-connection in `ConnectChannel`. Confirm exact field names for the pinned Graph version.

## 8. Required OAuth scopes (confirm against current Meta docs at build time)

`pages_show_list`, `pages_read_engagement`, `pages_manage_engagement`, `pages_messaging`,
`pages_manage_metadata`, `instagram_basic`, `instagram_manage_comments`,
`instagram_manage_messages`, `business_management`.

## 9. Routes (`routes/web.php`, inside `auth`+`verified`)

Tenant connections (owner/member with `manage-connections`):
- `GET  /connections` → `ConnectionController@index` (name `connections.index`)
- `GET  /connections/facebook/redirect` → `@redirect` (build OAuth URL + store `state` in session)
- `GET  /connections/facebook/callback` → `@callback` (handle code → show asset picker)
- `POST /connections` → `@store` (create `ChannelConnection`s for selected assets via `ConnectChannel`)
- `DELETE /connections/{channelConnection}` → `@destroy` (Policy-authorized; `DisconnectChannel`)

Webhook **verification only** (public, no auth), needed to register the callback in the dashboard:
- `GET /webhooks/meta` → returns `hub.challenge` when `hub.verify_token === config('services.meta.webhook_verify_token')`, else 403.
- `POST /webhooks/meta` → **Phase 4** (for now: return `200` and no-op, or leave a stub that just
  persists nothing). Do not implement processing here.

**OAuth security**: generate a random `state`, store in session, verify on callback (CSRF). Validate
the `redirect_uri` matches `config('app.url').'/connections/facebook/callback'`. Throttle the callback.

## 10. Policies & Form Requests

- `ChannelConnectionPolicy`: `viewAny`/`create`/`delete` require `manage-connections`; `delete` also
  requires the connection belongs to the acting tenant (resolve with `withoutTenantScope` + explicit
  `tenant_id` check — see route-binding caveat).
- `StoreConnectionRequest`: validate the selected asset ids/tokens from the callback session payload.

## 11. Frontend (Inertia + React, RTL/i18n)

- `resources/js/pages/connections/index.tsx`: list current connections (name, platform badge, status,
  webhook_subscribed), a **"Connect Facebook"** button linking to `connections.facebook.redirect`, and
  a disconnect action. Show a quota hint (used/max pages) — read from a shared prop or page prop.
- `resources/js/pages/connections/select.tsx` (or a modal): after callback, show the fetched Pages/IG
  accounts with checkboxes → POST to `connections.store`.
- Use `useTranslations()` + add keys to `lang/ar.json` & `lang/en.json`. Tailwind logical properties.
- After changing routes/pages: `php artisan wayfinder:generate --with-form` (note the `--with-form`
  flag — the vite plugin uses `formVariants: true`), then `npm run types:check`.

## 12. Token lifecycle (BARIQ §6.3)

- On connect, exchange for a **long-lived** token; store `token_expires_at`.
- Handle expiry/revocation: if a Graph call returns an auth error (code 190 / OAuthException), mark the
  connection `status = error` (or `revoked`) and notify the tenant owner (reuse the notification
  pattern from Phase 2). A refresh command can come later — leave a clean seam.

## 13. Do NOT (out of scope this phase)

- Webhook event **processing**, rule engine, reply dispatch (Phase 4/5).
- Automated payment gateway. WhatsApp. Multi-step flows.

## 14. Definition of done (per BARIQ §13)

Migration + `ChannelConnection` model + enums + DTOs + `MetaGraphClient` + `FacebookOAuthService` +
`ConnectChannel`/`DisconnectChannel` + Policy + Form Requests + controllers + routes + Inertia pages +
factory. `composer lint:check`, `composer types:check` (phpstan level 7), and `npm run types:check`
all clean. **No unencrypted tokens. No token or secret in any log. Quota enforced on connect.**
Leave the app in a state where a reviewer can add Pest tests for: OAuth callback handling (Graph
mocked with `Http::fake`), token stored encrypted, quota blocks over-limit connects, webhook GET
challenge verification, asset webhook subscription called, tenant isolation.

## 15. Verification the implementer should run (before handoff)

1. `php artisan migrate` succeeds; `channel_connections` exists.
2. `php artisan route:list | grep -E "connections|webhooks"` shows all routes.
3. With a tunnel + a real dev Meta app (see the setup checklist the product owner maintains): click
   **Connect Facebook**, complete the dialog, land on the picker, select a Page, and confirm a
   `channel_connections` row is created with `webhook_subscribed = true` and an **encrypted**
   `access_token` (verify the raw DB column is ciphertext, not the token).
4. `GET /webhooks/meta?hub.mode=subscribe&hub.verify_token=<token>&hub.challenge=123` returns `123`.
5. `composer lint:check && composer types:check && npm run types:check` all green.
