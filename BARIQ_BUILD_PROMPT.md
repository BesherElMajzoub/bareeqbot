# BARIQ — Build Prompt

> A production-grade, multi-tenant SaaS for automating replies on Facebook & Instagram
> (comments, story replies, story mentions) via the Meta Graph API.
> This document is the single source of truth for building the platform. Follow it strictly.

---

## 0. How to use this prompt

You are a **senior Laravel engineer**. Build **Bariq** module by module in the phase order defined in §11.
Do **not** scaffold everything at once. After each phase, stop and produce: migrations, models,
services, actions, form requests, policies, routes, and **Pest tests** for that phase only.
Ask before inventing product behavior that is not specified here.

---

## 1. Product context

Bariq lets a customer connect their Facebook Pages and Instagram accounts, then define automation
rules so the platform replies **automatically** to:

- **Post comments** (Facebook & Instagram) — public reply and/or private DM.
- **Story replies** (a user replies to the customer's story via DM).
- **Story mentions** (a user @mentions the customer's account in their story).

Each connected page/account can have **per-post custom replies** and keyword-based rules.
Bariq is sold as a subscription. **Billing is manual** (offline payment + admin activation), and
the **quota metric is the number of connected pages/accounts** allowed per plan.

Target markets: Saudi & Syrian. Because online card payment is unavailable/unreliable in some of
these markets, v1 uses manual activation — but the billing layer must be abstracted so an automated
gateway (Moyasar / Tap / PayTabs) can be plugged in later without rewriting subscription logic.

---

## 2. Tech stack & conventions

- **PHP 8.3+**, **Laravel 12+** (use the project's current major version).
- **MySQL 8** (or MariaDB), **Redis** for cache + queues + rate limiting.
- **Queue**: Redis + Horizon. Every outbound Meta call and every webhook processing runs on a queue.
- **Testing**: **Pest** (feature + unit). Aim for meaningful coverage of services, rule matching,
  quota enforcement, subscription lifecycle, and webhook parsing.
- **Packages**:
  - `spatie/laravel-permission` — roles/permissions (platform + tenant scoped).
  - `spatie/laravel-data` — DTOs.
  - `spatie/laravel-query-builder` — filtered/sorted index endpoints.
  - `laravel/horizon` — queue monitoring.
  - `laravel/sanctum` — API auth for the SPA/dashboard (or Fortify if server-rendered).
- **API version**: pin the Meta Graph API version in config (e.g. `META_GRAPH_VERSION=v23.0`).
  Confirm the current version and the exact webhook field names against live Meta docs at build time.

### Architectural rules (non-negotiable)
1. **Thin controllers, fat services.** Controllers only: validate (Form Request) → call an
   Action/Service → return a Resource. No business logic in controllers.
2. **Action classes** for single write use-cases (e.g. `ActivateSubscriptionAction`,
   `ConnectChannelAction`, `ExecuteRuleReplyAction`).
3. **Service classes** for domain orchestration and external integrations (e.g. `MetaGraphClient`,
   `RuleMatcher`, `ReplyDispatcher`).
4. **Repository pattern** only where it earns its keep (complex queries); otherwise Eloquent directly.
5. **DTOs** (`spatie/laravel-data`) for all cross-boundary data (webhook payloads, plan/quota data,
   Meta API responses).
6. **Policies** for every authorization decision. **Form Requests** for every input.
7. **All external tokens encrypted at rest** (`encrypted` cast). Never log tokens or raw secrets.
8. **Idempotency everywhere** on webhook side (a comment/message must never be replied to twice).

---

## 3. Multi-tenancy model

- **Row-level (shared database) multi-tenancy.** A `Tenant` = one customer account (organization).
- Every tenant-owned table has a `tenant_id` FK.
- Provide a `BelongsToTenant` trait applying a **global scope** that filters by the current tenant.
- Current tenant is resolved from the **authenticated user** for dashboard/API requests.
- **CRITICAL — webhook context has no authenticated user.** In queued webhook jobs, the tenant is
  resolved from the **Meta asset id** (page id / ig id) → `ChannelConnection` → `tenant_id`. Set the
  tenant context manually inside the job before touching tenant-scoped models. Never assume `auth()`.

---

## 4. Roles & permissions (Spatie)

**Platform roles** (Bariq staff):
- `super_admin` — full access, manage tenants, approve subscriptions, see global analytics.
- `support` — read tenants, activate/extend subscriptions, no destructive actions.

**Tenant roles** (scoped to a tenant):
- `owner` — manages billing, connections, rules, team, sees tenant analytics.
- `member` — manages rules & connections, no billing.

Guard platform admin routes behind a `super_admin`/`support` gate and a separate route group/prefix
(`/admin`). Tenant routes are guarded by tenant membership + role.

---

## 5. Database schema

> Types abbreviated. Add timestamps, soft deletes where noted, and sensible indexes/uniques.

### Tenancy & auth
- **users**: id, name, email (unique), password, is_platform_staff (bool), ...
- **tenants**: id, name, slug (unique), owner_user_id, status [active|suspended], ...
- **tenant_user** (pivot): tenant_id, user_id, role — or rely on Spatie teams.
- Spatie tables: roles, permissions, model_has_roles, etc.

### Billing (manual)
- **plans**: id, name, slug (unique), max_pages (int), features (json), is_active (bool), sort.
- **plan_prices**: id, plan_id, duration_months [1|3|6|12], price (decimal), currency, is_active.
- **subscription_requests**: id, tenant_id, plan_price_id, payment_proof_path (nullable),
  payer_note (nullable), status [pending|approved|rejected], reviewed_by (user_id, nullable),
  reviewed_at (nullable), reject_reason (nullable).
- **subscriptions**: id, tenant_id, plan_id, duration_months, price, currency, starts_at, ends_at,
  status [active|expired|cancelled], source [manual|gateway], created_by. Index (tenant_id, status).
  A tenant has at most **one active** subscription at a time.

### Channel connections
- **channel_connections**: id, tenant_id, platform [facebook|instagram],
  provider_account_id (page id or ig user id), linked_page_id (nullable, for IG-via-page),
  name, username (nullable), access_token (encrypted), token_expires_at (nullable),
  webhook_subscribed (bool), status [active|revoked|error], meta (json), connected_by (user_id).
  Unique (platform, provider_account_id).

### Automation
- **automation_rules**: id, tenant_id, channel_connection_id, name,
  trigger_surface [post_comment|story_reply|story_mention],
  target_scope [all|specific] (for comments only), target_ref (nullable post/media id),
  match_type [any|exact|contains|regex], keyword (nullable), case_sensitive (bool),
  priority (int), is_active (bool). Index (channel_connection_id, is_active, priority).
- **rule_actions**: id, rule_id, action_type [public_reply|private_reply|dm],
  message_template (text), delay_seconds (int, default 0), sort. Ordered execution.
  > `message_template` supports simple placeholders, e.g. `{{commenter_name}}`.

### Ingestion & logs (idempotency + audit)
- **webhook_events**: id, platform, object_type, object_id, signature_valid (bool),
  raw_payload (json), received_at, processed_at (nullable), status [received|processed|failed|skipped].
  Raw store enables debugging + replay.
- **reply_logs**: id, tenant_id, channel_connection_id, rule_id (nullable), platform,
  surface [post_comment|story_reply|story_mention], source_object_id (comment/message id),
  actor_id (commenter id, nullable), action_type, status [sent|failed|skipped|deduped],
  error (nullable), responded_at (nullable).
  **Unique (platform, source_object_id, action_type)** — the primary idempotency guard.

### Analytics (aggregates)
- **daily_stats**: id, tenant_id, channel_connection_id (nullable), date,
  events_received (int), replies_sent (int), dms_sent (int), failures (int).
  Unique (tenant_id, channel_connection_id, date). Populated by a nightly command.

---

## 6. Module specs

### 6.1 Tenancy & Auth
- Registration creates a `User` + a `Tenant` (user becomes `owner`).
- Invite flow for `member` users (email invite → join tenant).
- `SetCurrentTenant` middleware resolves + binds the active tenant for the request.

### 6.2 Manual billing & subscriptions
- **Plans** are seeded (see §7). Public pricing page lists plans × durations.
- **Subscription request**: owner picks a `plan_price`, optionally uploads a transfer receipt,
  submits → `subscription_requests` row (pending). Notify platform staff.
- **Admin review** (`/admin`): approve → `ActivateSubscriptionAction`:
  - sets `starts_at = now()`, `ends_at = now()->addMonths(duration)`,
  - marks any previous active subscription of that tenant as replaced/expired,
  - creates `subscriptions` row (active), marks request approved.
  - Reject → status rejected + reason, notify tenant.
- **Expiry**: scheduled command `subscriptions:expire` (daily) sets `status = expired` where
  `ends_at < now()`, and pauses automation for tenants with no active subscription.
- **Quota gate**: a `SubscriptionQuota` service exposes `maxPages(tenant)`, `usedPages(tenant)`,
  `canConnectMore(tenant)`. Enforced when connecting a channel and when running automation
  (skip/queue-drop automation for over-quota or expired tenants).
- **Billing abstraction**: define a `BillingProvider` contract with a `ManualProvider` implementation.
  Automated gateways later implement the same contract (webhook → same `ActivateSubscriptionAction`).

### 6.3 Channel connections (Meta OAuth)
- **Single Bariq Meta App** used by all tenants. Customers connect via **Facebook Login for Business**.
- OAuth flow: redirect → callback → exchange short-lived → **long-lived user token** →
  fetch `/me/accounts` (pages) and linked IG business accounts → let owner pick which to connect →
  store **Page Access Token** (and IG access) encrypted per `channel_connection`.
- On connect: enforce quota (`canConnectMore`), then **subscribe the asset to webhooks** (§8).
- Handle token refresh/expiry and revocation (mark connection `error`/`revoked`, notify owner).
- Required scopes: `pages_show_list`, `pages_read_engagement`, `pages_manage_engagement`,
  `pages_messaging`, `pages_manage_metadata`, `instagram_basic`, `instagram_manage_comments`,
  `instagram_manage_messages`, `business_management`. (Confirm exact list against current Meta docs.)

### 6.4 Webhook ingestion
- **One** public endpoint: `GET /webhooks/meta` (verification challenge) +
  `POST /webhooks/meta` (events).
- `GET`: return `hub_challenge` when `hub_verify_token` matches config.
- `POST`:
  1. Verify `X-Hub-Signature-256` HMAC against the app secret (constant-time compare) → 403 on fail.
  2. Persist a `webhook_events` row (raw payload).
  3. Dispatch `ProcessMetaWebhook` job.
  4. Return `200` immediately (Meta retries on non-200 / slowness).
- The job:
  - Parses per-entry changes. **Route by object + field**:
    - Facebook page `feed` → item `comment`, verb `add` → **comment rule pipeline**.
    - Instagram `comments` → **comment rule pipeline**.
    - Messaging events with a `story_reply` / `story_mention` context → **story pipeline**.
  - Resolves the tenant via the asset id → `ChannelConnection`.
  - Skips self-authored comments (page/account replying to itself) to avoid loops.
  - Enforces active-subscription + quota before replying.

### 6.5 Rule engine & reply dispatch
- **`RuleMatcher`**: given (connection, surface, target ref, text), returns the first matching active
  rule by `priority`. Matching honors `target_scope`/`target_ref` (specific post vs all) and
  `match_type` (any/exact/contains/regex) + `keyword` + `case_sensitive`.
- **`ReplyDispatcher`**: iterates the rule's `rule_actions` in order and dispatches each:
  - `public_reply` → `POST /{comment_id}/comments` (Instagram: `/{ig_comment_id}/replies`).
  - `private_reply` → `POST /{comment_id}/private_replies`.
  - `dm` → messaging send API (for story replies/mentions, within the allowed window).
  - Renders `message_template` placeholders. Applies `delay_seconds` via delayed jobs.
- **Idempotency**: before any send, insert/lock `reply_logs` on
  (platform, source_object_id, action_type); duplicates → `deduped`, no send.
- **Meta constraints to encode** (validate current values against docs):
  - **Private reply: one per comment/post, within 7 days**, only if the user messaged/commented on
    the page surface and hasn't blocked page messages. Public replies have no such limit.
  - Standard messaging window applies to follow-up DMs after a user interaction.

### 6.6 Stories (v1 scope)
- **Story reply**: user replies to the customer's story → arrives as a messaging event with a
  story-reply context → match a `story_reply` rule → send `dm` action.
- **Story mention**: user @mentions the customer's account in their story → mention webhook →
  match a `story_mention` rule → send `dm` action.
- Store the story/media reference on the `reply_logs` row for analytics.

### 6.7 Analytics
- **Live counters** from `reply_logs` (indexed) for recent windows.
- **Nightly aggregation** command `stats:aggregate` fills `daily_stats` per tenant + connection.
- Dashboard (tenant): events received, replies sent, DMs sent, failures, success rate, top rules,
  per-page breakdown, time-series. (Platform admin: same, aggregated across all tenants.)
- Expose via a filtered API (`spatie/laravel-query-builder`).

### 6.8 Admin panel (`/admin`)
- Manage tenants (suspend/activate), review + approve/reject subscription requests,
  extend/expire subscriptions manually, view global usage & analytics, inspect webhook_events
  for debugging.

---

## 7. Seed data

- Plans (example — adjust pricing later):
  - **Starter**: `max_pages = 1`.
  - **Growth**: `max_pages = 5`.
  - **Business**: `max_pages = 15`.
  - **Agency**: `max_pages = 50`.
- Each plan × durations **1 / 3 / 6 / 12 months** → `plan_prices` rows (discount longer durations).
- Seed platform `super_admin` user and Spatie roles/permissions.

---

## 8. Meta integration details

- Subscribe at **two levels** (both required, or events never fire):
  1. App-level: `POST /{APP_ID}/subscriptions` with `object=page` / `object=instagram`,
     `callback_url`, `verify_token`, and fields (`feed`, `comments`, `messages`, etc.).
  2. Asset-level: `POST /{PAGE_ID}/subscribed_apps` with the subscribed fields.
- Webhook fields to subscribe: at minimum `feed` (FB), `comments` + `messages` (IG),
  and messaging fields needed for story replies/mentions. Confirm exact field names for the
  pinned Graph API version.
- **`MetaGraphClient` service**: central HTTP client (base url + pinned version), token injection,
  retry with backoff, rate-limit awareness (respect Meta headers), typed responses (DTOs), and
  structured error handling. All Graph calls go through it.
- Never trust the webhook payload to be complete — hydrate missing fields
  (e.g. comment `attachment`/`parent`) with a follow-up `GET` when needed.

---

## 9. Non-functional requirements

- **Security**: signed webhooks, encrypted tokens, no secrets in logs, policies on every action,
  rate limiting on public endpoints, CSRF on dashboard, throttled OAuth callbacks.
- **Reliability**: all outbound Meta work on queues with retries + dead-letter handling; webhook
  endpoint returns fast; idempotent replies; replayable `webhook_events`.
- **Observability**: Horizon for queues; structured logs with correlation ids; failed reply logs
  are visible in admin.
- **Performance**: index hot paths (reply_logs uniques, rule lookups); cache plan/quota lookups in
  Redis; aggregate analytics nightly rather than querying raw logs for dashboards.

---

## 10. Testing (Pest)

Cover at least:
- Subscription lifecycle: request → approve → active → expire; only one active per tenant.
- Quota: cannot connect beyond `max_pages`; automation paused when expired/over quota.
- Webhook: signature verification (valid/invalid), challenge verification, idempotency (no double
  reply), self-comment skipping, tenant resolution from asset id.
- `RuleMatcher`: priority ordering, target scope (specific post vs all), all `match_type` variants.
- `ReplyDispatcher`: correct endpoints per action_type, template rendering, dedupe path,
  private-reply constraint handling.
- Analytics aggregation correctness.

---

## 11. Build phases (implement in this order)

1. **Foundation** — auth, tenancy (BelongsToTenant + global scope), roles/permissions, plans + seeds.
2. **Manual billing** — subscription requests, admin approve/reject, subscription lifecycle,
   expiry command, quota service + gate. (No Meta yet.)
3. **Meta OAuth connect** — Facebook Login, page/IG selection, encrypted token storage,
   webhook subscription on connect, quota enforcement.
4. **Webhook ingestion** — endpoint, challenge, signature verify, raw store, queue job,
   tenant resolution, dedupe scaffolding.
5. **Rule engine + FB comment replies** — rules CRUD, RuleMatcher, ReplyDispatcher (public +
   private reply), idempotent reply_logs.
6. **Instagram comments + Stories** — IG comment replies, story_reply + story_mention DM pipelines.
7. **Analytics** — reply_logs surfacing, nightly aggregation, tenant + admin dashboards.
8. **Hardening** — admin polish, rate limits, token refresh/revocation handling, full Pest pass.

---

## 12. Decisions deferred (do not build yet; leave clean seams)

- Automated payment gateway (Moyasar / Tap / PayTabs) behind the `BillingProvider` contract.
- **Multi-step conversational flows** (ManyChat-style branching): add `flow_steps` + per-user
  conversation state later. v1 is single-shot rules (one match → ordered actions).
- Team billing roles beyond owner/member.
- WhatsApp channel.

---

## 13. Definition of done (per phase)

Migrations + models + services/actions + form requests + policies + routes + resources + Pest tests,
all green, with the phase's behavior demonstrably working end to end. No business logic in
controllers. No unencrypted tokens. No path that can reply twice to the same object.
