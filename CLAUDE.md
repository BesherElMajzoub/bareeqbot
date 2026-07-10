# CLAUDE.md — Bariq

Guidance for AI agents working in this repository. Keep it current as the app grows.

## What Bariq is

**Bariq (بريق)** is a production-grade, multi-tenant SaaS that automates replies on **Facebook &
Instagram** (post comments, story replies, story mentions) via the **Meta Graph API**. Billing is
manual (offline payment + admin activation); the quota metric is the number of connected
pages/accounts per plan. Target markets: Saudi Arabia & Syria (Arabic-first UI).

**[BARIQ_BUILD_PROMPT.md](BARIQ_BUILD_PROMPT.md) is the single source of truth for product
behavior.** Follow it. Ask before inventing behavior it does not specify. Build **phase by phase**
(see the roadmap below) and stop after each phase for review — do not scaffold everything at once.

## Tech stack (actual)

- **PHP 8.3+ / Laravel 13**, **MySQL 8** (dev DB `bareeqBot`, test DB `bareeqbot_testing`).
- **Frontend**: Inertia v3 + **React 19** + TypeScript + Tailwind v4 + Wayfinder. shadcn-style UI in
  `resources/js/components/ui`.
- **Auth**: **Fortify** (email verification, 2FA, passkeys). No Sanctum — the dashboard is a
  server-rendered Inertia SPA.
- **Testing**: **Pest** (`tests/Pest.php` binds `TestCase` + `RefreshDatabase`).
- **Queue**: `database` (active default). Every outbound Meta call and webhook is wrapped in a Job.
  `predis/predis` is installed (pure-PHP Redis client) and `config/queue.php`'s `redis` connection is
  ready — switch via `QUEUE_CONNECTION=redis` in production. **`laravel/horizon` is NOT installed**:
  it requires `ext-pcntl`/`ext-posix`, which do not exist on Windows PHP builds at all (not a config
  gap — Horizon cannot run on this dev machine under any configuration). Install it in the
  Linux/WSL/Docker production environment: `composer require laravel/horizon`, then
  `php artisan horizon:install` and gate the dashboard to `super_admin` only.
- **Packages**: `spatie/laravel-permission` (roles, teams-enabled), `spatie/laravel-data` (DTOs),
  `spatie/laravel-query-builder` (filtered admin/analytics listings), `predis/predis`.

## Commands

```bash
composer dev            # serve + queue:listen + vite (concurrently)
composer test           # config:clear + pint --test + phpstan + artisan test
php artisan test        # Pest suite (add --compact, or --filter=Name)
vendor/bin/pest         # Pest directly
composer lint           # pint (fix)   |  composer lint:check (dry run)
composer types:check    # phpstan (larastan)
php artisan migrate     # migrations against bareeqBot
php artisan db:seed     # roles/permissions + plans + super admin
npm run types:check     # tsc --noEmit
```

## Non-negotiable architecture rules (from BARIQ_BUILD_PROMPT §2)

1. **Thin controllers, fat services.** Controllers only: Form Request → Action/Service → Resource.
   No business logic in controllers.
2. **Action classes** for single write use-cases (e.g. `app/Actions/Tenancy/CreateTenant.php`).
3. **Service classes** for domain orchestration + external integrations (`MetaGraphClient`,
   `RuleMatcher`, `ReplyDispatcher`).
4. **DTOs** (`spatie/laravel-data`, `app/Data`) for all cross-boundary data (webhook payloads,
   plan/quota data, Meta API responses).
5. **Policy + Form Request for every authorization decision and every input.**
6. **All external tokens encrypted at rest** (`encrypted` cast). **Never log tokens or raw secrets.**
7. **Idempotency everywhere on the webhook side.** A comment/message must never be replied to twice
   (guarded by a unique index on `reply_logs`).

## Multi-tenancy (critical)

Row-level, shared-database. Every tenant-owned table has `tenant_id`.

- Add the **`App\Concerns\BelongsToTenant`** trait to every tenant-owned model. It applies
  `App\Scopes\TenantScope` (filters by the active tenant) and auto-fills `tenant_id` on create.
- The active tenant lives in **`App\Support\TenantContext`** (a container singleton).
- For web/API requests, **`App\Http\Middleware\SetCurrentTenant`** resolves the tenant from the
  authenticated user and also sets Spatie's team id.
- **⚠️ Webhook jobs have NO authenticated user.** Resolve the tenant from the Meta asset id →
  `ChannelConnection` → `tenant_id`, then set it manually (`TenantContext::set()` /
  `PermissionRegistrar::setPermissionsTeamId()`) **before** touching any tenant-scoped model. Never
  assume `auth()` in a job.
- Bypass the scope deliberately with `Model::withoutTenantScope()`.
- **Route-model binding caveat:** `SubstituteBindings` runs *before* `SetCurrentTenant`, so implicit
  bindings are NOT tenant-scoped yet. Platform `/admin` relies on this (cross-tenant, fine). For
  future tenant-owned resource routes (rules, connections), resolve/authorize explicitly (Policy +
  `withoutTenantScope` or a scoped binding) rather than trusting the ambient scope.

### Roles & permissions (Spatie, teams-enabled)

- Teams are on; the team key is `tenant_id`. Roles are created **global** (team id null); the
  **assignment** carries the team id.
- **Tenant roles** (assigned with the tenant's id): `owner`, `member`.
- **Platform roles** (assigned with the sentinel `config('bariq.platform_team_id')` = `0`, since real
  tenant ids start at 1): `super_admin`, `support`. Platform staff also carry
  `users.is_platform_staff = true`.
- Enums: `App\Enums\{TenantRole, PlatformRole, TenantStatus}`.

## Localization / RTL

Arabic-first. `APP_LOCALE=ar`; the root `<html>` gets `dir="rtl"` for `ar/fa/he/ur`. Translations
live in `lang/{ar,en}.json`, are shared to the frontend via `HandleInertiaRequests`, and read with
the `useTranslations()` hook (`t('key')`). **Use `t()` in UI, not hardcoded strings.** Prefer Tailwind
logical properties (`ms-*`, `pe-*`, `text-start`) so components work in both directions.

## Security & reliability

Signed webhooks (HMAC SHA-256, constant-time compare), encrypted tokens, no secrets in logs,
policies on every action, rate limiting on public endpoints, throttled OAuth callbacks. All outbound
Meta work on queues with retries; webhook endpoint returns fast; replies idempotent;
`webhook_events` stores raw payloads for replay/debugging.

## Directory map

| Path | Purpose |
|------|---------|
| `app/Actions/` | Single write use-cases |
| `app/Services/` | Domain orchestration + Meta integration |
| `app/Data/` | `spatie/laravel-data` DTOs |
| `app/Policies/` | Authorization |
| `app/Concerns/`, `app/Scopes/`, `app/Support/` | Tenancy trait, scope, `TenantContext` |
| `app/Enums/` | Roles, statuses, surfaces |
| `resources/js/pages/` | Inertia React pages |
| `lang/` | i18n JSON dictionaries |
| `tests/Feature`, `tests/Unit` | Pest tests (roles seeded in `TestCase::setUp`) |

## Testing notes

- Tests run against **MySQL `bareeqbot_testing`** (configured in `phpunit.xml`). `RefreshDatabase`
  migrates + wraps each test in a transaction.
- `TestCase::setUp` seeds `RolesAndPermissionsSeeder` for any test using `RefreshDatabase` (both Pest
  and class-based), because registration assigns the `owner` role.
- Write new tests with **Pest** (`test()` / `it()` + `expect()`). Prefer specific assertions
  (`assertOk`, `assertRedirect`). Cover services, rule matching, quota, subscription lifecycle,
  webhook parsing/idempotency as those phases land.
- The `tenancy_test_fixtures` table + `Tests\Fixtures\TenancyTestFixture` exist only to exercise
  `BelongsToTenant` (the migration is guarded to the testing env).

## Build roadmap (BARIQ §11) — build in order, stop after each

1. **Foundation** ✅ — auth, tenancy, roles/permissions, plans + seeds. *(done)*
2. **Manual billing** ✅ — subscription requests, admin approve/reject, subscription lifecycle,
   `subscriptions:expire` (scheduled daily), `SubscriptionQuota` service, `BillingProvider` seam
   (`ManualProvider`), `/admin` review guarded by `platform.staff`. *(done)*
3. **Meta OAuth connect** ✅ — Facebook Login for Business, page/IG selection, `encrypted`
   `ChannelConnection.access_token`, `MetaGraphClient`/`FacebookOAuthService`, webhook subscription on
   connect, quota enforcement. *(done)*
4. **Webhook ingestion** ✅ — `GET /webhooks/meta` challenge + signed `POST` (`meta.signature`
   HMAC middleware) → raw `webhook_events` store → `ProcessMetaWebhook` job → `MetaWebhookParser`
   (normalized `IncomingWebhookEvent` DTOs) → tenant resolved from asset id via
   `ChannelConnectionResolver`, self-authored skip, active-subscription gate, event-level idempotency.
   Rule matching/replies are the Phase 5 seam. *(done)*
5. **Rule engine + FB comment replies** ✅ — `automation_rules`/`rule_actions`/`reply_logs`, rules
   CRUD (`manage-rules`), `RuleMatcher` (priority + target scope + any/exact/contains/regex),
   `ReplyDispatcher` → queued idempotent `SendReply` (unique `reply_logs (platform,
   source_object_id, action_type)`), `TemplateRenderer` (`{{placeholder}}`), wired into
   `ProcessMetaWebhook`. *(done)*
6. **Instagram comments + Stories** ✅ — IG comment replies (`/replies` edge), `story_reply` +
   `story_mention` → `RuleActionType::Dm` via `MetaGraphClient::sendDirectMessage` (Send API),
   `reply_logs.parent_ref` stores the story/media reference. *(done)*
7. **Analytics** ✅ — `AnalyticsService` (live counters from `reply_logs`: summary, daily series,
   top rules), nightly `stats:aggregate` → `daily_stats` (idempotent per date), tenant dashboard +
   `/admin/analytics` (platform-wide), filtered reply log (`spatie/laravel-query-builder`) at
   `/analytics/logs`. *(done)*
8. **Hardening** ✅ — admin polish (`/admin/tenants` suspend/activate — a real kill switch checked in
   `ProcessMetaWebhook`; read-only `/admin/webhook-events` inspector; full sidebar nav wired to every
   tenant + admin page, admin section gated on `is_platform_staff`), rate limiting (`meta-webhook`
   named limiter, 300/min/IP, on both webhook routes; Fortify already throttles login/2FA/passkeys),
   Redis + Horizon seam (see Tech stack note — `predis` installed, Horizon deferred to a POSIX
   environment), token refresh/revocation (`MetaApiException::isAuthError()` → `MarkConnectionError`
   marks the connection `error` + notifies the owner once, from `SendReply`; `DisconnectChannel` now
   also calls `MetaGraphClient::unsubscribeAppFromPage` on manual disconnect), full Pest pass
   (143 tests) + `npm run lint:check` + `format:check` + `types:check` all clean. *(done)*

All 8 phases are complete. Remaining follow-ups are explicitly deferred, not gaps: an automated
payment gateway behind `BillingProvider`, multi-step conversational flows, WhatsApp, and running
Horizon (needs a POSIX host).

## Definition of done (per phase)

Migrations + models + services/actions + form requests + policies + routes + resources + Pest tests,
all green. No business logic in controllers. No unencrypted tokens. No path that can reply twice to
the same object. Confirm Meta Graph version + webhook field names against live Meta docs before
shipping any Meta integration.
