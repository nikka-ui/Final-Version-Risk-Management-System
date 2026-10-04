# Laravel Migration Notes

Incremental cutover from Express (`docker/web`) to Laravel (`backend/`) is **complete**. Blade owns the workflow edge. Express source and the `web` service are removed (Phase 9 slice 8). Phase 10 made Postgres the sole live SoT (dual-write off). Phase 11 wired `ai-service` through admin reclassify. Phase 12 added CI. Phase 13 adds a CPU transformer classify engine. Phase 14 added optional Next.js UI at `/app`. Phase 15 closes the migration track.

## Phase 0–5 slice 17 (complete)

APIs, dual-write, Blade login, profiles, full Ticket Reporter GET console, admin dashboard + users + departments + positions.

## Phase 5 slice 18 (Admin tickets list — complete)

| Piece | Notes |
|-------|--------|
| `GET /laravel/admin/tickets` | Ticket list with search/filters + soft-delete dialog |
| `USE_LARAVEL_ADMIN_TICKETS_UI` | Compose default **`true`** |
| Delete POST | Phase 7 slice 5: Blade (`POST /admin/tickets/:ref/delete`) |
| Ticket detail | Still Express (`GET /admin/tickets/:ref`) — read-only view |
| Health | `phase: 5`, `slice: 18` |

### Verify

```powershell
docker compose -f docker/compose.yml -f docker/compose.override.yml up --build -d
docker compose -f docker/compose.yml -f docker/compose.override.yml exec api php artisan rms:smoke-slice5-admin-tickets
```

### Opt out

```powershell
docker compose -f docker/compose.yml -f docker/compose.override.yml -f docker/compose.soak.yml up -d web
```

## Phase 5 slice 19 (Admin ticket detail — complete)

| Piece | Notes |
|-------|--------|
| `GET /laravel/admin/tickets/:ref` | Ticket detail read-only view |
| `USE_LARAVEL_ADMIN_TICKET_DETAIL_UI` | Compose default **`true`** |
| Express redirect | `GET /admin/tickets/:ref` redirects to Blade when enabled |
| Health | `phase: 5`, `slice: 19` |

### Verify

```powershell
docker compose -f docker/compose.yml -f docker/compose.override.yml exec api php artisan rms:smoke-slice5-admin-tickets-detail
```

## Phase 5 slice 20 (Admin audit logs — complete)

| Piece | Notes |
|-------|--------|
| `GET /laravel/admin/audit-logs` | Audit log list (search/filter + details dialog) |
| `USE_LARAVEL_ADMIN_AUDIT_LOGS_UI` | Compose default **`true`** |
| Express redirect | `GET /admin/audit-logs` redirects to Blade when enabled |
| Export CSV | Phase 8 slice 7: Laravel `GET /admin/audit-logs/export` |
| Health | `phase: 5`, `slice: 20` |

### Verify

```powershell
docker compose -f docker/compose.yml -f docker/compose.override.yml exec api php artisan rms:smoke-slice5-admin-audit-logs
```

## Phase 5 slice 21 (Admin system settings — complete)

| Piece | Notes |
|-------|--------|
| `GET /laravel/admin/settings` | System settings form (landing, AI, security, backup) |
| `USE_LARAVEL_ADMIN_SETTINGS_UI` | Compose default **`true`** |
| Express redirect | `GET /admin/settings` redirects to Blade when enabled |
| Save / reset POSTs | Still Express (`POST /admin/settings*`); success redirects back to Blade |
| Health | `phase: 5`, `slice: 21` |

### Verify

```powershell
docker compose -f docker/compose.yml -f docker/compose.override.yml exec api php artisan rms:smoke-slice5-admin-settings
```

## Phase 5 slice 22 (Department Head dashboard — complete)

| Piece | Notes |
|-------|--------|
| `GET /laravel/dept` | Department Head overview (KPIs + recent tickets) |
| `USE_LARAVEL_DEPT_DASHBOARD_UI` | Compose default **`true`** |
| Express redirect | `GET /dept` redirects to Blade when enabled |
| Queues / detail | Still Express (`/dept/inbox`, `/dept/tickets/:ref`, …) |
| Health | `phase: 5`, `slice: 22` |

### Verify

```powershell
docker compose -f docker/compose.yml -f docker/compose.override.yml exec api php artisan rms:smoke-slice5-dept-dashboard
```

## Phase 5 slice 23 (Department Head queues — complete)

| Piece | Notes |
|-------|--------|
| `GET /laravel/dept/{inbox,active,drafts,returned,overdue,closure,tickets}` | Queue lists from Postgres |
| `USE_LARAVEL_DEPT_QUEUES_UI` | Compose default **`true`** |
| Express redirect | Matching Express GETs redirect to Blade when enabled |
| Ticket detail | Still Express (`GET /dept/tickets/:ref`) — mutations stay Express |
| Health | `phase: 5`, `slice: 23` |

### Verify

```powershell
docker compose -f docker/compose.yml -f docker/compose.override.yml exec api php artisan rms:smoke-slice5-dept-queues
```

## Phase 5 slice 24 (Department Head ticket detail)

| Piece | Notes |
|-------|--------|
| `GET /laravel/dept/tickets/:ref` | Detail view with ownership / action-plan / close forms |
| `USE_LARAVEL_DEPT_TICKET_DETAIL_UI` | Compose default **`true`** |
| Express redirect | `GET /dept/tickets/:ref` redirects to Blade when enabled |
| Mutations | Still Express POSTs; success redirects back to Blade |
| Health | `phase: 5`, `slice: 24` |

### Verify

```powershell
docker compose -f docker/compose.yml -f docker/compose.override.yml exec api php artisan rms:smoke-slice5-dept-ticket-detail
```

## Phase 5 slice 25 (Risk Management Officer dashboard)

| Piece | Notes |
|-------|--------|
| `GET /laravel/officer` | RMO governance dashboard (stats, departments, 5×5 matrix) |
| `USE_LARAVEL_OFFICER_DASHBOARD_UI` | Compose default **`true`** |
| Express redirect | `GET /officer` redirects to Blade when enabled |
| Queues / detail | Still Express (`/officer/tickets`, overdue, monitoring, action-plans) |
| Health | `phase: 5`, `slice: 25` |

### Verify

```powershell
docker compose -f docker/compose.yml -f docker/compose.override.yml exec api php artisan rms:smoke-slice5-officer-dashboard
```

## Phase 5 slice 26 (Risk Management Officer queues)

| Piece | Notes |
|-------|--------|
| `GET /laravel/officer/{tickets,overdue,monitoring,action-plans}` | Queue list views |
| `USE_LARAVEL_OFFICER_QUEUES_UI` | Compose default **`true`** |
| Express redirect | Matching Express GETs redirect to Blade when enabled |
| Ticket detail | Still Express (`GET /officer/tickets/:ref`) — mutations stay Express |
| Health | `phase: 5`, `slice: 26` |

### Verify

```powershell
docker compose -f docker/compose.yml -f docker/compose.override.yml exec api php artisan rms:smoke-slice5-officer-queues
```

## Phase 5 slice 27 (Risk Management Officer ticket detail — current)

| Piece | Notes |
|-------|--------|
| `GET /laravel/officer/tickets/:ref` | Governance detail (read-only + reopen + discussion) |
| `USE_LARAVEL_OFFICER_TICKET_DETAIL_UI` | Compose default **`true`** |
| Express redirect | `GET /officer/tickets/:ref` redirects to Blade when enabled |
| Mutations | Thread-comment / reopen POSTs stay on Express; success redirects back to Blade |
| Health | `phase: 5`, `slice: 27` |

### Verify

```powershell
docker compose -f docker/compose.yml -f docker/compose.override.yml exec api php artisan rms:smoke-slice5-officer-ticket-detail
```

## Phase 6 slice 6 (Employee `/dashboard`)

| Piece | Notes |
|-------|--------|
| nginx `GET /dashboard` | Laravel Blade stub (employees) or redirect to `/{role}` console |
| `USE_LARAVEL_DASHBOARD_UI` | Compose default **`true`** |
| Express redirect | `GET /dashboard` redirects to Blade when enabled |
| Health | `phase: 6`, `slice: 6` |

### Verify

```powershell
docker compose -f docker/compose.yml -f docker/compose.override.yml up -d nginx api web
docker compose -f docker/compose.yml -f docker/compose.override.yml exec api php artisan rms:smoke-slice6-dashboard
curl.exe -sI http://localhost:8080/dashboard
```

Expected: guest `/dashboard` → Laravel `302` to `/login` (no `X-Powered-By: Express`).

### Opt out

```powershell
docker compose -f docker/compose.yml -f docker/compose.override.yml -f docker/compose.soak.yml up -d nginx api web
```

## Phase 6 slice 5 (RMO legacy aliases)

| Piece | Notes |
|-------|--------|
| nginx `GET /officer/{ai-review,review,final-validation}` | Laravel aliases → `/officer/tickets` or `/officer/action-plans` |
| `USE_LARAVEL_OFFICER_ALIASES_UI` | Compose default **`true`** |
| Express redirect | Matching Express GETs redirect to Laravel aliases when enabled |
| Health | `phase: 6`, `slice: 5` |

### Verify

```powershell
docker compose -f docker/compose.yml -f docker/compose.override.yml up -d nginx api web
docker compose -f docker/compose.yml -f docker/compose.override.yml exec api php artisan rms:smoke-slice6-officer-aliases
curl.exe -sI http://localhost:8080/officer/ai-review
```

Expected: guest `/officer/ai-review` → Laravel `302` to `/login` (no `X-Powered-By: Express`).

### Opt out

```powershell
docker compose -f docker/compose.yml -f docker/compose.override.yml -f docker/compose.soak.yml up -d nginx api web
```

## Phase 6 slice 4 (Executive ticket detail)

| Piece | Notes |
|-------|--------|
| nginx `GET /executive/tickets/:ref` | Laravel Blade |
| `USE_LARAVEL_EXECUTIVE_TICKET_DETAIL_UI` | Compose default **`true`** |
| Express redirect | `GET /executive/tickets/:ref` redirects to Blade when enabled |
| Mutations | Comment POSTs stay on Express; success redirects back to Blade |
| Exceptions | Attachments + notifications stay Express |
| Health | `phase: 6`, `slice: 4` |

### Verify

```powershell
docker compose -f docker/compose.yml -f docker/compose.override.yml up -d nginx api web
docker compose -f docker/compose.yml -f docker/compose.override.yml exec api php artisan rms:smoke-slice6-executive-ticket-detail
curl.exe -sI http://localhost:8080/executive/tickets/RISK-FAKE-1
```

Expected: guest `/executive/tickets/:ref` → Laravel `302` to `/login` (no `X-Powered-By: Express`).

### Opt out

```powershell
docker compose -f docker/compose.yml -f docker/compose.override.yml -f docker/compose.soak.yml up -d nginx api web
```

## Phase 6 slice 3 (Executive oversight pages)

| Piece | Notes |
|-------|--------|
| nginx `GET /executive/{heatmap,reports,trends,statistics,departments,register,critical}` | Laravel Blade |
| `USE_LARAVEL_EXECUTIVE_PAGES_UI` | Compose default **`true`** |
| Express redirect | Matching Express GETs redirect to Blade when enabled |
| Exceptions | Attachments, notifications stay Express (ticket detail is slice 4) |
| Health | `phase: 6`, `slice: 3` |

### Verify

```powershell
docker compose -f docker/compose.yml -f docker/compose.override.yml up -d nginx api web
docker compose -f docker/compose.yml -f docker/compose.override.yml exec api php artisan rms:smoke-slice6-executive-pages
curl.exe -sI http://localhost:8080/executive/heatmap
```

Expected: guest `/executive/heatmap` → `302` to `/login`; signed-in executive → Blade heatmap.

### Opt out

```powershell
docker compose -f docker/compose.yml -f docker/compose.override.yml -f docker/compose.soak.yml up -d nginx api web
```

## Phase 6 slice 2 (Unprefixed Blade URLs)

| Piece | Notes |
|-------|--------|
| nginx `GET /login` | Laravel Blade (POST `/login` also Laravel) |
| nginx `GET /admin`, `/supervisor`, `/dept`, `/officer`, `/president`, `/executive` | Laravel; POSTs stay Express |
| Exceptions | Attachments, audit CSV export stay Express (officer aliases are slice 5)
| Blade links | Canonical unprefixed (`/admin/users`, not `/laravel/admin/users`) |
| `/laravel/*` | Still rewritten to Laravel (bookmarks) |
| `USE_LARAVEL_EDGE_UI` | Compose default **`true`**. Soak nginx uses `docker/nginx/soak/` (no unprefixed locations) |
| Health | `phase: 6`, `slice: 2` |

### Verify

```powershell
docker compose -f docker/compose.yml -f docker/compose.override.yml up -d nginx api web
docker compose -f docker/compose.yml -f docker/compose.override.yml exec api php artisan rms:smoke-slice6-edge-ui
curl.exe -sI http://localhost:8080/login
curl.exe -sI http://localhost:8080/admin
```

Expected: `/login` → `200` Blade Sign In; `/admin` → `302` to `/login` when signed out.

### Opt out

```powershell
docker compose -f docker/compose.yml -f docker/compose.override.yml -f docker/compose.soak.yml up -d nginx api web
```

## Phase 6 slice 1 (Edge `/` → Laravel)

| Piece | Notes |
|-------|--------|
| nginx `location = /` | Proxies exact `/` to Laravel `api:8080` |
| Laravel `GET /` | Guest → `/login`; signed-in → `/{role}` when edge UI is on |
| `USE_LARAVEL_EDGE_ROOT` | Compose default **`true`**. Soak sets **`false`** so `/` redirects to Express `/login` |
| Health | `phase: 6`, `slice: 1` |

### Verify

```powershell
docker compose -f docker/compose.yml -f docker/compose.override.yml exec api php artisan rms:smoke-slice6-edge-root
curl.exe -sI http://localhost:8080/
```

## Phase 5 slice 31 (President ticket detail)

| Piece | Notes |
|-------|--------|
| `GET /laravel/president/tickets/:ref` | Ticket detail with action-plan / final decision UI |
| `USE_LARAVEL_PRESIDENT_TICKET_DETAIL_UI` | Compose default **`true`** |
| Express redirect | `GET /president/tickets/:ref` redirects to Blade when enabled |
| Mutations | Decision / comment POSTs stay on Express; success redirects back to Blade |
| Health | `phase: 5`, `slice: 31` |

### Verify

```powershell
docker compose -f docker/compose.yml -f docker/compose.override.yml exec api php artisan rms:smoke-slice5-president-ticket-detail
```

## Phase 5 slice 30 (President queues)

| Piece | Notes |
|-------|--------|
| `GET /laravel/president/{pending,high,critical,trends}` | Queue lists + monthly trends chart |
| `USE_LARAVEL_PRESIDENT_QUEUES_UI` | Compose default **`true`** |
| Express redirect | `/president/{pending,high,critical,trends}` redirect to Blade when enabled |
| Health | `phase: 5`, `slice: 30` |

### Verify

```powershell
docker compose -f docker/compose.yml -f docker/compose.override.yml exec api php artisan rms:smoke-slice5-president-queues
```

Incremental cutover from Express (`docker/web`) to Laravel (`backend/`) is **complete**. Blade owns the workflow edge. Express source and the `web` service are removed (Phase 9 slice 8). Phase 10 made Postgres the sole live SoT (dual-write off). Phase 11 wired `ai-service` classify/summarize through admin reclassify (slice 6). Phase 12 added CI verification (PHPUnit, ai-service tests, Trivy image scan). Phase 13 adds a CPU transformer hybrid behind the same `/classify` contract. Phase 14 added optional Next.js UI at `/app`. Phase 15 closes the migration track.

## Phase 16 slice 3 (Next.js admin users/settings/audit — current)

| Piece | Notes |
|-------|--------|
| `/app/users` | Admin user create/edit/activate/deactivate/reset/delete |
| `/app/settings` | System settings GET/PATCH + landing/AI reset |
| `/app/audit-logs` | Audit list + CSV export |
| APIs | `/api/v1/users*`, `/api/v1/settings*`, `/api/v1/audit-logs*` |
| Health | API + frontend → `phase: 16`, `slice: 3` |

### Verify

```powershell
docker compose -f docker/compose.yml -f docker/compose.override.yml up --build -d nginx api ai-service
docker compose -f docker/compose.yml -f docker/compose.override.yml --profile frontend up --build -d frontend
docker compose -f docker/compose.yml -f docker/compose.override.yml exec api php artisan rms:import-users
docker compose -f docker/compose.yml -f docker/compose.override.yml exec api php artisan rms:smoke-phase15-migration
curl.exe -s http://localhost:8080/api/v1/health
```

Expected: health `phase:16`,`slice:3`. Sign in at `/app/login` as `admin` / `a3c1993` for users, settings, and audit logs.

## Phase 16 slice 2 (Next.js workflow mutations — complete)

| Piece | Notes |
|-------|--------|
| Ticket detail | Dept accept/reject/plan/return/reassign/close/personnel; president decision; officer reopen; comments |
| Reporter revision | `PATCH /api/v1/tickets/:ref` for `draft`, `returned`, and `ownership_rejected` |
| Admin org | `/app/departments` and `/app/positions` create/edit/deactivate |
| Blade | Parallel console |
| Health | API + frontend → `phase: 16`, `slice: 2` |

### Verify

```powershell
docker compose -f docker/compose.yml -f docker/compose.override.yml up --build -d nginx api ai-service
docker compose -f docker/compose.yml -f docker/compose.override.yml --profile frontend up --build -d frontend
docker compose -f docker/compose.yml -f docker/compose.override.yml exec api php artisan rms:import-users
docker compose -f docker/compose.yml -f docker/compose.override.yml exec api php artisan rms:smoke-phase15-migration
curl.exe -s http://localhost:8080/api/v1/health
```

Expected: health `phase:16`,`slice:2`. Sign in at `/app/login` as `dephead` / `a3c1993` to accept an assigned ticket, or as `admin` to create a department.

## Phase 16 slice 1 (Next.js reporter create/submit — complete)

| Piece | Notes |
|-------|--------|
| `/app/tickets/new` | Reporter form → `POST /api/v1/tickets` + optional evidence upload |
| Submit | `POST /api/v1/tickets/:ref/submit` (draft → assigned) |
| Draft edit/delete | Ticket detail for owned drafts |
| Blade | Still owns accept/reject/close/uploads for later workflow |
| Health | API + frontend → `phase: 16`, `slice: 1` |

### Verify

```powershell
docker compose -f docker/compose.yml -f docker/compose.override.yml up --build -d nginx api ai-service
docker compose -f docker/compose.yml -f docker/compose.override.yml --profile frontend up --build -d frontend
docker compose -f docker/compose.yml -f docker/compose.override.yml exec api php artisan rms:import-users
docker compose -f docker/compose.yml -f docker/compose.override.yml exec api php artisan rms:smoke-phase15-migration
curl.exe -s http://localhost:8080/api/v1/health
```

Expected: health `phase:16`,`slice:1`. Sign in at `/app/login` as `reporter` / `a3c1993` and create a report at `/app/tickets/new`.

## Phase 15 slice 1 (Migration closure — complete)

| Piece | Notes |
|-------|--------|
| Status | **Express → Laravel migration complete** |
| Workflow UI | Blade on `:8080` (accept/reject/close/uploads unchanged) |
| Next.js UI | `/app` read views: login, dashboard, tickets, notifications, departments |
| Auth | Sanctum bearer tokens; Blade session login separate at `/login` |
| `store.json` | Import-only; dual-write flags default **off** |
| CI | Trivy scans `rms-frontend` image |
| Health | API + frontend → `phase: 15`, `slice: 1`, `migration: complete` |

### Verify

```powershell
docker compose -f docker/compose.yml -f docker/compose.override.yml up --build -d nginx api ai-service
docker compose -f docker/compose.yml -f docker/compose.override.yml --profile frontend up --build -d frontend
docker compose -f docker/compose.yml -f docker/compose.override.yml exec api php artisan rms:import-users
docker compose -f docker/compose.yml -f docker/compose.override.yml exec api php artisan rms:smoke-phase15-migration
curl.exe -s http://localhost:8080/api/v1/health
curl.exe -s http://localhost:8080/app/tickets
```

Expected: health reports `migration: complete`. Sign in at `/app/login` as `admin` / `a3c1993`. Blade workflow remains at `/login`.

## Phase 14 slice 3 (Sanctum bearer login — complete)

| Piece | Notes |
|-------|--------|
| `/app/login` | Next.js form → `POST /api/v1/auth/token` (Sanctum bearer) |
| `/app/dashboard` | Protected page → `GET /api/v1/users/me` |
| Token storage | `sessionStorage` in browser (not Blade cookie session) |
| Health | API + frontend JSON → `phase: 14`, `slice: 3` |
| Smoke | `rms:smoke-phase14-frontend` checks health, Sanctum token + `/users/me`, nginx `/app` |

### Verify

```powershell
docker compose -f docker/compose.yml -f docker/compose.override.yml up --build -d nginx api ai-service
docker compose -f docker/compose.yml -f docker/compose.override.yml --profile frontend up --build -d frontend
docker compose -f docker/compose.yml -f docker/compose.override.yml exec api php artisan rms:smoke-phase14-frontend
curl.exe -s http://localhost:8080/app/health
```

Expected: smoke reports Sanctum bearer auth OK. Open http://localhost:8080/app/login and sign in as `admin` / `a3c1993`. Blade login unchanged at `/login`.

## Phase 14 slices 4–8 (Next.js read UI — complete)

| Slice | Feature |
|-------|---------|
| 4 | App shell + role-aware dashboard |
| 5 | `/app/tickets` list with search/filters |
| 6 | `/app/tickets/:ref` detail (5W1H) |
| 7 | `/app/notifications` + mark-all-read |
| 8 | `/app/departments` registry view |

## Phase 14 slice 2 (nginx /app proxy — complete)

| Piece | Notes |
|-------|--------|
| nginx | `GET /app/` → `frontend:3000` (requires `--profile frontend`) |
| Next.js | `basePath: /app`; browser uses same-origin `/api/v1` via nginx |
| Home page | Client fetch to `/api/v1/health` proves edge + API wiring |
| Health | API + frontend JSON → `phase: 14`, `slice: 2` |
| Smoke | `rms:smoke-phase14-frontend` checks API, frontend, and nginx `/app/health` |

### Verify

```powershell
docker compose -f docker/compose.yml -f docker/compose.override.yml up --build -d nginx api ai-service
docker compose -f docker/compose.yml -f docker/compose.override.yml --profile frontend up --build -d frontend
docker compose -f docker/compose.yml -f docker/compose.override.yml exec api php artisan rms:smoke-phase14-frontend
curl.exe -s http://localhost:8080/api/v1/health
curl.exe -s http://localhost:8080/app/health
curl.exe -s http://localhost:3000/app/health
```

Expected: API health `phase:14`,`slice:2`. `/app/health` via nginx and direct frontend both report `framework: nextjs`. Blade login still at `http://localhost:8080/login`.

## Phase 14 slice 1 (Next.js frontend scaffold — complete)

| Piece | Notes |
|-------|--------|
| `frontend/` | Next.js 14 App Router + TypeScript; standalone Docker build |
| Compose profile | `frontend` service on `127.0.0.1:3000` — **does not replace Blade on :8080** |
| `NEXT_PUBLIC_API_URL` | Defaults to `/api/v1` (browser calls Laravel via nginx when proxied later) |
| Health | `GET /health` → `phase: 14`, `slice: 1` |
| API health | `phase: 14`, `slice: 1` |
| CI | `npm run build` in `.github/workflows/ci.yml` |

### Verify

```powershell
docker compose -f docker/compose.yml -f docker/compose.override.yml up --build -d nginx api ai-service
docker compose -f docker/compose.yml -f docker/compose.override.yml --profile frontend up --build -d frontend
docker compose -f docker/compose.yml -f docker/compose.override.yml exec api php artisan rms:smoke-phase14-frontend
curl.exe -s http://localhost:8080/api/v1/health
curl.exe -s http://localhost:3000/health
```

Expected: API health `phase:14`,`slice:1`. Frontend `/health` reports `framework: nextjs`, `phase:14`,`slice:1`. Blade login still at `http://localhost:8080/login`.

## Phase 13 slice 1 (CPU transformer hybrid classify — complete)

| Piece | Notes |
|-------|--------|
| Flask `POST /classify` | Taxonomy + TF-IDF NLP hybrid, then CPU transformer encoder (`transformer-hybrid-v1`) |
| Flask `POST /summarize` | Same engine, summary-only payload |
| Device | CPU only (no GPU required); `device: cpu` on health and classify |
| `transformerScores` | Top category/department cosine scores from the transformer encoder |
| Contract | Same JSON as Phase 11; Blade and Laravel APIs unchanged |
| PHP stub | Still `DraftAiTaxonomy` when ai-service is down |
| Health | `phase: 13`, `slice: 1` |

### Verify

```powershell
docker compose -f docker/compose.yml -f docker/compose.override.yml up --build -d nginx api ai-service
docker compose -f docker/compose.yml -f docker/compose.override.yml up -d --force-recreate nginx
docker compose -f docker/compose.yml -f docker/compose.override.yml exec ai-service python test_classify.py
docker compose -f docker/compose.yml -f docker/compose.override.yml exec api php artisan rms:smoke-phase13-ai
docker compose -f docker/compose.yml -f docker/compose.override.yml exec api php artisan rms:smoke-slice11-ai
curl.exe -s http://localhost:8080/api/v1/health
curl.exe -s http://localhost:8080/ai-health
```

Expected: health `phase:13`,`slice:1`. `/ai-health` reports `mode: transformer-hybrid`, `device: cpu`. Smoke still classifies a network outage as operational → Information Technology.

## Phase 12 slice 2 (Trivy image scan in CI — complete)

| Piece | Notes |
|-------|--------|
| `.github/workflows/ci.yml` `trivy` job | Builds `rms-api` + `rms-ai-service`; pulls `nginx:1.30-alpine`; scans with Trivy |
| Gate | Fails the job on **fixable CRITICAL** CVEs (`ignore-unfixed: true`) |
| Report | Table log of CRITICAL/HIGH plus SARIF upload to GitHub code scanning |
| Health | `phase: 12`, `slice: 2` |

### Verify

```powershell
cd backend; composer install; composer test
docker compose -f docker/compose.yml -f docker/compose.override.yml up --build -d nginx api ai-service
docker compose -f docker/compose.yml -f docker/compose.override.yml exec api php artisan rms:smoke-phase12-ci
curl.exe -s http://localhost:8080/api/v1/health
```

Expected: local `composer test` passes. Stack health `phase:12`,`slice:2`. Smoke gate OK. Push/PR also runs Trivy on api, ai-service, and nginx images.

Optional local scan (requires Docker):

```powershell
docker run --rm -v /var/run/docker.sock:/var/run/docker.sock aquasec/trivy:0.64.1 image --severity CRITICAL --ignore-unfixed rms-api:latest
```

## Phase 12 slice 1 (GitHub Actions CI — complete)

| Piece | Notes |
|-------|--------|
| `.github/workflows/ci.yml` | PHPUnit on push/PR; ai-service `test_classify.py` job |
| `composer test` | Runs `php artisan test` in `backend/` |
| `rms:smoke-phase12-ci` | Docker stack health gate; uses `RMS_SMOKE_API_URL` default `http://127.0.0.1:8080/v1/health` inside the api container |
| Health | `phase: 12`, `slice: 1` |

### Verify

```powershell
cd backend; composer install; composer test
docker compose -f docker/compose.yml -f docker/compose.override.yml up --build -d nginx api ai-service
docker compose -f docker/compose.yml -f docker/compose.override.yml exec api php artisan rms:smoke-phase12-ci
curl.exe -s http://localhost:8080/api/v1/health
```

Expected: local `composer test` passes. Stack health `phase:12`,`slice:1`. Smoke gate OK.

## Phase 11 slice 6 (Admin ticket AI reclassify — complete)

| Piece | Notes |
|-------|--------|
| `POST /api/v1/tickets/{ref}/ai/reclassify` | Sanctum + admin; re-runs classify, persists history, refreshes `risk_tickets.ai` |
| `POST /admin/tickets/{ref}/reclassify` | Blade admin ticket detail button |
| Workflow | Status, department, and priority on the ticket are **not** changed |
| Live display | `risk_tickets.ai` + category/likelihood/impact updated after reclassify |
| Health | `phase: 11`, `slice: 6` |

### Verify

```powershell
docker compose -f docker/compose.yml -f docker/compose.override.yml up --build -d nginx api ai-service
docker compose -f docker/compose.yml -f docker/compose.override.yml up -d --force-recreate nginx
docker compose -f docker/compose.yml -f docker/compose.override.yml exec api php artisan rms:smoke-slice11-ai-reclassify
curl.exe -s http://localhost:8080/api/v1/health
```

Expected: health `phase:11`,`slice:6`. Smoke reclassifies a ticket, persists a new history row, refreshes live AI fields without changing workflow status.

## Phase 11 slice 5 (NLP hybrid classify + PHP taxonomy stub — complete)

| Piece | Notes |
|-------|--------|
| Flask `POST /classify` | Taxonomy-v1 base + scikit-learn TF-IDF cosine refinement (`nlp-hybrid-v1`) |
| Flask `POST /summarize` | Same hybrid engine, summary-only payload |
| `nlpScores` | Top category/department similarity scores on each classify result |
| PHP stub | `DraftAiTaxonomy` mirrors Express ISO categories when ai-service is down |
| Health | `phase: 11`, `slice: 5` |

### Verify

```powershell
docker compose -f docker/compose.yml -f docker/compose.override.yml up --build -d nginx api ai-service
docker compose -f docker/compose.yml -f docker/compose.override.yml up -d --force-recreate nginx
docker compose -f docker/compose.yml -f docker/compose.override.yml exec ai-service python test_classify.py
docker compose -f docker/compose.yml -f docker/compose.override.yml exec api php artisan rms:smoke-slice11-ai
curl.exe -s http://localhost:8080/api/v1/health
curl.exe -s http://localhost:8080/ai-health
```

Expected: health `phase:11`,`slice:5`. `/ai-health` reports `mode: nlp-hybrid`. Smoke classifies network outage as operational → IT; PHP fallback uses taxonomy-v1.

## Phase 11 slice 4 (Express taxonomy classify — complete)

| Piece | Notes |
|-------|--------|
| Flask `POST /classify` | Port of Express `generateAiAnalysisFromReport` (ISO categories, weighted department routing, mitigation templates) |
| Flask `POST /summarize` | Same taxonomy, summary-only payload |
| Department labels | Mapped onto Laravel org seed names (e.g. IT → Information Technology) |
| Laravel client | Unchanged HTTP contract; PHP stub remains last-resort fallback |
| Health | `phase: 11`, `slice: 4` |

### Verify

```powershell
docker compose -f docker/compose.yml -f docker/compose.override.yml up --build -d nginx api ai-service
docker compose -f docker/compose.yml -f docker/compose.override.yml up -d --force-recreate nginx
docker compose -f docker/compose.yml -f docker/compose.override.yml exec ai-service python test_classify.py
docker compose -f docker/compose.yml -f docker/compose.override.yml exec api php artisan rms:smoke-slice11-ai
curl.exe -s http://localhost:8080/api/v1/health
curl.exe -s http://localhost:8080/ai-health
```

Expected: health `phase:11`,`slice:4`. `/ai-health` reports `mode: taxonomy`. Smoke classifies a network outage as operational → Information Technology.

## Phase 11 slice 3 (AI history Blade + API — complete)

| Piece | Notes |
|-------|--------|
| `GET /admin/ai-analysis` | Admin list of classify runs (search/filter by ticket, source, category) |
| Admin ticket detail | Recent AI runs strip + link to history |
| `GET /api/v1/tickets/{ref}/ai-analysis` | Sanctum ticket-scoped history |
| Live display | Still `risk_tickets.ai` JSON |
| Health | `phase: 11`, `slice: 3` |

### Verify

```powershell
docker compose -f docker/compose.yml -f docker/compose.override.yml up --build -d nginx api
docker compose -f docker/compose.yml -f docker/compose.override.yml up -d --force-recreate nginx
docker compose -f docker/compose.yml -f docker/compose.override.yml exec api php artisan rms:smoke-slice11-ai-history
curl.exe -s http://localhost:8080/api/v1/health
curl.exe -sI http://localhost:8080/admin/ai-analysis
```

Expected: health `phase:11`,`slice:3`. Smoke renders the admin AI history Blade. Guest `/admin/ai-analysis` redirects to login.

## Phase 11 slice 2 (AI analysis history table — complete)

| Piece | Notes |
|-------|--------|
| `ai_analysis_results` table | One row per classify run (source, scores, input/result JSON) |
| `AiAnalysisService::analyze` | Persists after remote or PHP-stub result; optional `ticket_reference` |
| Live display | Still `risk_tickets.ai` JSON (Blade unchanged) |
| Import/list | `listForTicket($reference)` for history reads |
| Health | `phase: 11`, `slice: 2` |

### Verify

```powershell
docker compose -f docker/compose.yml -f docker/compose.override.yml up --build -d nginx api ai-service
docker compose -f docker/compose.yml -f docker/compose.override.yml exec api php artisan migrate --force
docker compose -f docker/compose.yml -f docker/compose.override.yml exec api php artisan rms:smoke-slice11-ai-results
curl.exe -s http://localhost:8080/api/v1/health
```

Expected: health `phase:11`,`slice:2`. Smoke persists a classify row and lists it by ticket reference.

## Phase 11 slice 1 (AI classify via ai-service — complete)

| Piece | Notes |
|-------|--------|
| Flask `POST /classify` | Heuristic analysis matching Laravel `DraftAiAnalysis` contract |
| Flask `POST /summarize` | Thin summary from the same heuristics |
| Laravel `AiAnalysisService` | HTTP to `AI_SERVICE_URL`; falls back to PHP stub on failure |
| Ticket storage | Still `risk_tickets.ai` JSON (unchanged Blade contract) |
| Health | `phase: 11`, `slice: 1` |

### Verify

```powershell
docker compose -f docker/compose.yml -f docker/compose.override.yml up --build -d nginx api ai-service
docker compose -f docker/compose.yml -f docker/compose.override.yml up -d --force-recreate nginx
docker compose -f docker/compose.yml -f docker/compose.override.yml exec api php artisan rms:smoke-slice11-ai
curl.exe -s http://localhost:8080/api/v1/health
curl.exe -s http://localhost:8080/ai-health
```

Expected: health `phase:11`,`slice:1`. Smoke classify via ai-service + PHP stub fallback. `/ai-health` reports `mode: heuristic`.

## Phase 10 slice 3 (Postgres sole SoT — dual-write off — complete)

| Piece | Notes |
|-------|--------|
| `USE_LARAVEL_INTERNAL_TICKETS` | Default **`false`** — no ticket writes to `store.json` |
| `USE_LARAVEL_INTERNAL_ORG` | Default **`false`** — no org/settings/user writes to `store.json` |
| Audits | Still written to Postgres when mirrors are off (`ExpressOrgMirrorService::persistAudit`) |
| `store.json` | Import-only (`rms:import-*`); optional re-enable via the two flags |
| Flags cleanup | Obsolete Express `USE_LARAVEL_*_UI` / mutation flags removed from `.env.example` |
| Health | `phase: 10`, `slice: 3` |

### Verify

```powershell
docker compose -f docker/compose.yml -f docker/compose.override.yml up --build -d nginx api
docker compose -f docker/compose.yml -f docker/compose.override.yml up -d --force-recreate nginx
docker compose -f docker/compose.yml -f docker/compose.override.yml exec api php artisan rms:smoke-slice10-no-dual-write
curl.exe -s http://localhost:8080/api/v1/health
```

Expected: health `phase:10`,`slice:3`. Smoke confirms dual-write flags off, Postgres audit write, `store.json` hash unchanged.

## Phase 10 slice 2 (Admin settings on Postgres — complete)

| Piece | Notes |
|-------|--------|
| `system_settings` table | Postgres SoT for admin settings Blade GET/POST |
| Reads | `AdminSettingsService::get()` uses Postgres row or in-code defaults (no `store.json` read) |
| Import | `php artisan rms:import-settings` |
| `store.json` | Still dual-writes `systemSettings` for compatibility |
| Health | `phase: 10`, `slice: 2` |

### Verify

```powershell
docker compose -f docker/compose.yml -f docker/compose.override.yml up --build -d nginx api
docker compose -f docker/compose.yml -f docker/compose.override.yml exec api php artisan rms:import-settings
docker compose -f docker/compose.yml -f docker/compose.override.yml exec api php artisan rms:smoke-slice10-settings
curl.exe -s http://localhost:8080/api/v1/health
```

Expected: health `phase:10`,`slice:2`. Smoke reads defaults then writes/reads a Postgres settings row.

## Phase 10 slice 1 (Admin audit logs on Postgres — complete)

| Piece | Notes |
|-------|--------|
| `audit_logs` table | Postgres SoT for admin audit list, CSV export, dashboard recent strip |
| Writes | `AuditLogService` via store dual-write path (org/ticket mirrors) + fallback when store write fails |
| Import | `php artisan rms:import-audit-logs` |
| `store.json` | Still dual-writes `auditLogs` for compatibility |
| Health | `phase: 10`, `slice: 1` |

### Verify

```powershell
docker compose -f docker/compose.yml -f docker/compose.override.yml up --build -d nginx api
docker compose -f docker/compose.yml -f docker/compose.override.yml exec api php artisan migrate --force
docker compose -f docker/compose.yml -f docker/compose.override.yml exec api php artisan rms:smoke-slice10-audit-logs
curl.exe -s http://localhost:8080/api/v1/health
```

Expected: health `phase:10`,`slice:1`. Smoke records + lists a Postgres audit row.

## Phase 9 slice 8 (Remove Express `web` source)

| Piece | Notes |
|-------|--------|
| compose | No `web` service. nginx + `api` only. |
| `docker/web` | Deleted. |
| `store.json` | `docker/data/store.json` mounted at `/import/store.json`. |
| Dual-write | Laravel writes store.json in-process (no HTTP to Express). |
| Health | `phase: 9`, `slice: 8` |

### Verify

```powershell
docker compose -f docker/compose.yml -f docker/compose.override.yml up --build -d nginx api
docker compose -f docker/compose.yml -f docker/compose.override.yml exec api php artisan rms:smoke-slice9-remove-express
curl.exe -sI http://localhost:8080/login
docker compose -f docker/compose.yml -f docker/compose.override.yml config --services
```

Expected: `/login` → Laravel `200`. Compose services do not include `web`.

## Phase 9 slice 7 (Retire Express `web` from the default stack)

| Piece | Notes |
|-------|--------|
| compose `web` | Profile **`express`**. Default `up` does not start `rms-web`. |
| nginx `depends_on` | `api` + `ai-service` only (no `web`). |
| soak | Matches Laravel (flags ON). Does not swap nginx to Express. |
| Express fallback | `-f docker/compose.express.yml --profile express` |
| Health | `phase: 9`, `slice: 7` |

### Verify

```powershell
docker compose -f docker/compose.yml -f docker/compose.override.yml up --build -d nginx api
docker compose -f docker/compose.yml -f docker/compose.override.yml up -d --force-recreate nginx
docker compose -f docker/compose.yml -f docker/compose.override.yml exec api php artisan rms:smoke-slice9-retire-web
curl.exe -sI http://localhost:8080/login
docker compose -f docker/compose.yml -f docker/compose.override.yml ps
```

Expected: `/login` → Laravel (`rms_api_session`, no `X-Powered-By: Express`). `rms-web` is not running.

### Express fallback

```powershell
docker compose -f docker/compose.yml -f docker/compose.override.yml -f docker/compose.express.yml --profile express up -d
```

## Phase 9 slice 6 (Org dual-write internals on Laravel)

| Piece | Notes |
|-------|--------|
| nginx `POST /internal/org/*` | Laravel writes Express `store.json` (departments, positions, users, settings). |
| nginx `^~ /internal/` | All dual-write internals → Laravel (`api:8080`). |
| `USE_LARAVEL_INTERNAL_ORG` | Compose default **`true`**; soak **`false`** (Express keeps org internals). |
| Health | `phase: 9`, `slice: 6` |

### Verify

```powershell
docker compose -f docker/compose.yml -f docker/compose.override.yml up --build -d nginx api web
docker compose -f docker/compose.yml -f docker/compose.override.yml up -d --force-recreate nginx
docker compose -f docker/compose.yml -f docker/compose.override.yml exec api php artisan rms:smoke-slice9-internal-org
curl.exe -sI -X POST http://localhost:8080/internal/org/users
```

Expected: guest POST → Laravel `401` JSON (no `X-Powered-By: Express`). `/internal/tickets/` still Laravel.

### Opt out

```powershell
docker compose -f docker/compose.yml -f docker/compose.override.yml -f docker/compose.soak.yml up -d nginx api web
```

## Phase 9 slice 5 (Ticket dual-write internals on Laravel)

| Piece | Notes |
|-------|--------|
| nginx `POST /internal/tickets/*` | Laravel writes Express `store.json` (upsert, soft-delete, delete-draft). Exact `^~ /internal/tickets/`. |
| `USE_LARAVEL_INTERNAL_TICKETS` | Compose default **`true`**; soak **`false`** (Express keeps ticket internals). |
| nginx `^~ /internal/` | Slice 6 sends all `/internal/` to Laravel. |
| Health | `phase: 9`, `slice: 5` |

### Verify

```powershell
docker compose -f docker/compose.yml -f docker/compose.override.yml up --build -d nginx api web
docker compose -f docker/compose.yml -f docker/compose.override.yml up -d --force-recreate nginx
docker compose -f docker/compose.yml -f docker/compose.override.yml exec api php artisan rms:smoke-slice9-internal-tickets
curl.exe -sI -X POST http://localhost:8080/internal/tickets/upsert
```

Expected: guest POST → Laravel `401` JSON (no `X-Powered-By: Express`). Slice 6 also sends `/internal/org/` to Laravel.

### Opt out

```powershell
docker compose -f docker/compose.yml -f docker/compose.override.yml -f docker/compose.soak.yml up -d nginx api web
```

## Phase 9 slice 4 (Unmatched edge fallback on Laravel)


| Piece | Notes |
|-------|--------|
| nginx unmatched `location /` | Laravel (`api:8080`). Serves `public/favicon.ico`, `robots.txt`, and Laravel 404s. |
| nginx `^~ /internal/` | Still Express (Laravel→`store.json` dual-write). |
| Soak | `docker/nginx/soak/rms.conf` keeps unmatched `location /` on Express. |
| Health | `phase: 9`, `slice: 4` |

### Verify

```powershell
docker compose -f docker/compose.yml -f docker/compose.override.yml up --build -d nginx api web
docker compose -f docker/compose.yml -f docker/compose.override.yml up -d --force-recreate nginx
docker compose -f docker/compose.yml -f docker/compose.override.yml exec api php artisan rms:smoke-slice9-edge-fallback
curl.exe -sI http://localhost:8080/favicon.ico
curl.exe -sI http://localhost:8080/rms-edge-fallback-probe
```

Expected: favicon → Laravel `200` (no `X-Powered-By: Express`); unknown path → Laravel `404` (no Express).

### Opt out

```powershell
docker compose -f docker/compose.yml -f docker/compose.override.yml -f docker/compose.soak.yml up -d nginx api web
```

## Phase 9 slice 3 (Logout on Laravel)


| Piece | Notes |
|-------|--------|
| nginx `GET`/`POST /logout` | Laravel clears the web session and redirects to `/login`. Exact `location =`. |
| `USE_LARAVEL_LOGIN_UI` | Same compose default **`true`**; soak **`false`** (Express keeps `/logout`). |
| nginx `^~ /internal/` | Still Express (Laravel→`store.json` dual-write). |
| Health | `phase: 9`, `slice: 3` |

### Verify

```powershell
docker compose -f docker/compose.yml -f docker/compose.override.yml up --build -d nginx api web
docker compose -f docker/compose.yml -f docker/compose.override.yml up -d --force-recreate nginx
docker compose -f docker/compose.yml -f docker/compose.override.yml exec api php artisan rms:smoke-slice9-logout
curl.exe -sI http://localhost:8080/logout
```

Expected: guest URL → Laravel `302` to `/login` (no `X-Powered-By: Express`).

### Opt out

```powershell
docker compose -f docker/compose.yml -f docker/compose.override.yml -f docker/compose.soak.yml up -d nginx api web
```

## Phase 9 slice 2 (Login bridge on Laravel)


| Piece | Notes |
|-------|--------|
| nginx `GET /auth/bridge` | Laravel consumes the one-time code and redirects to the role console (`next` allowed). Exact `location =`. |
| `USE_LARAVEL_LOGIN_UI` | Same compose default **`true`**; soak **`false`** (Express keeps the cookie bridge). |
| nginx `^~ /internal/` | Still Express (Laravel→`store.json` dual-write). |
| Health | `phase: 9`, `slice: 2` |

### Verify

```powershell
docker compose -f docker/compose.yml -f docker/compose.override.yml up --build -d nginx api web
docker compose -f docker/compose.yml -f docker/compose.override.yml up -d --force-recreate nginx
docker compose -f docker/compose.yml -f docker/compose.override.yml exec api php artisan rms:smoke-slice9-auth-bridge
curl.exe -sI http://localhost:8080/auth/bridge
```

Expected: guest URL → Laravel `302` to `/login?error=auth_unavailable` (no `X-Powered-By: Express`).

### Opt out

```powershell
docker compose -f docker/compose.yml -f docker/compose.override.yml -f docker/compose.soak.yml up -d nginx api web
```

## Phase 9 slice 1 (Blade static assets on Laravel)

| Piece | Notes |
|-------|--------|
| nginx `GET /css/*` + `/img/*` | Laravel `public/css` and `public/img` (copied from Express `docker/web/public`) |
| Soak | Still omits `blade-roots.conf`, so soak keeps Express static |
| Still Express | `/internal/*` dual-write mirrors, unmatched `location /` |
| Health | `phase: 9`, `slice: 1` |

### Verify

```powershell
docker compose -f docker/compose.yml -f docker/compose.override.yml up --build -d nginx api web
docker compose -f docker/compose.yml -f docker/compose.override.yml up -d --force-recreate nginx
docker compose -f docker/compose.yml -f docker/compose.override.yml exec api php artisan rms:smoke-slice9-static-assets
curl.exe -sI http://localhost:8080/css/app.css
```

Expected: guest CSS URL → `200` `text/css` (no `X-Powered-By: Express`).

### Opt out

```powershell
docker compose -f docker/compose.yml -f docker/compose.override.yml -f docker/compose.soak.yml up -d nginx api web
```

## Phase 8 slice 9 (Role attachment downloads)

| Piece | Notes |
|-------|--------|
| nginx `GET /{supervisor,dept,officer,executive,president}/attachments/:id` | Laravel streams MinIO/S3 bytes after role ticket visibility checks. `^~ /{role}/attachments/` now goes to Laravel. |
| Ticket-detail UI flags | Same compose defaults **`true`**; soak **`false`** |
| Express | Redirects to `/laravel/{role}/attachments/:id` when the matching ticket-detail UI flag is on |
| Health | `phase: 8`, `slice: 9` |

### Verify

```powershell
docker compose -f docker/compose.yml -f docker/compose.override.yml up --build -d nginx api web
docker compose -f docker/compose.yml -f docker/compose.override.yml up -d --force-recreate nginx
docker compose -f docker/compose.yml -f docker/compose.override.yml exec api php artisan rms:smoke-slice8-role-attachments
curl.exe -sI http://localhost:8080/dept/attachments/x
```

Expected: guest URL → Laravel `302` to `/login` (no `X-Powered-By: Express`).

### Opt out

```powershell
docker compose -f docker/compose.yml -f docker/compose.override.yml -f docker/compose.soak.yml up -d nginx api web
```

## Phase 8 slice 8 (Other-role notification open)

| Piece | Notes |
|-------|--------|
| nginx `GET /{dept,officer,executive,president}/notifications/open/:id` | Laravel mark-read + redirect to role ticket/home. `^~ /{role}/notifications/` now goes to Laravel (read-all still uses exact `location =`). |
| Dashboard UI flags | Same compose defaults **`true`**; soak **`false`** |
| Express | Redirects to `/laravel/{role}/notifications/open/:id` when the matching dashboard UI flag is on |
| Still Express | Role attachment downloads |
| Health | `phase: 8`, `slice: 8` |

### Verify

```powershell
docker compose -f docker/compose.yml -f docker/compose.override.yml up --build -d nginx api web
docker compose -f docker/compose.yml -f docker/compose.override.yml up -d --force-recreate nginx
docker compose -f docker/compose.yml -f docker/compose.override.yml exec api php artisan rms:smoke-slice8-role-notification-open
curl.exe -sI http://localhost:8080/dept/notifications/open/x
```

Expected: guest URL → Laravel `302` to `/login` (no `X-Powered-By: Express`).

### Opt out

```powershell
docker compose -f docker/compose.yml -f docker/compose.override.yml -f docker/compose.soak.yml up -d nginx api web
```

## Phase 8 slice 7 (Admin audit CSV export)

| Piece | Notes |
|-------|--------|
| nginx `GET /admin/audit-logs/export` | Laravel CSV (same filters as the list, limit 1000). Exact `location =` so it wins over prefix `/admin`. |
| `USE_LARAVEL_ADMIN_AUDIT_LOGS_UI` | Same compose default **`true`**; soak **`false`** |
| Express | Redirects to `/laravel/admin/audit-logs/export` when the flag is on |
| Still Express | Notification open links for other roles |
| Health | `phase: 8`, `slice: 7` |

### Verify

```powershell
docker compose -f docker/compose.yml -f docker/compose.override.yml up --build -d nginx api web
docker compose -f docker/compose.yml -f docker/compose.override.yml up -d --force-recreate nginx
docker compose -f docker/compose.yml -f docker/compose.override.yml exec api php artisan rms:smoke-slice8-admin-audit-export
curl.exe -sI http://localhost:8080/admin/audit-logs/export
```

Expected: guest URL → Laravel `302` to `/login` (no `X-Powered-By: Express`).

### Opt out

```powershell
docker compose -f docker/compose.yml -f docker/compose.override.yml -f docker/compose.soak.yml up -d nginx api web
```

## Phase 8 slice 6 (Other-role notifications read-all)

| Piece | Notes |
|-------|--------|
| nginx `POST /{dept,officer,executive,president}/notifications/read-all` | Laravel mark-all-read (CSRF). Exact `location =` so it wins over `^~ /{role}/notifications/` (open GETs stay Express). |
| Dashboard UI flags | Same compose defaults **`true`**; soak **`false`** |
| Still Express | Notification open links for other roles |
| Health | `phase: 8`, `slice: 6` |

### Verify

```powershell
docker compose -f docker/compose.yml -f docker/compose.override.yml up --build -d nginx api web
docker compose -f docker/compose.yml -f docker/compose.override.yml up -d --force-recreate nginx
docker compose -f docker/compose.yml -f docker/compose.override.yml exec api php artisan rms:smoke-slice8-role-notifications
curl.exe -sI http://localhost:8080/dept/notifications/read-all
```

Expected: guest URL → Laravel `302` to `/login` or `405 Allow: POST` (no `X-Powered-By: Express`).

### Opt out

```powershell
docker compose -f docker/compose.yml -f docker/compose.override.yml -f docker/compose.soak.yml up -d nginx api web
```

## Phase 8 slice 5 (Comment edit/react)

| Piece | Notes |
|-------|--------|
| nginx `POST /dept/tickets/:ref/comment/{edit,react}` | Laravel own-comment edit + emoji reaction toggle |
| nginx `POST /supervisor/tickets/:ref/comment` + `/comment/{edit,react}` | Laravel reporter thread add/edit/react |
| `USE_LARAVEL_DEPT_TICKET_MUTATIONS` | Compose default **`true`** (dept edit/react) |
| `USE_LARAVEL_REPORTER_TICKET_MUTATIONS` | Compose default **`true`** (reporter comment + edit/react) |
| Express mirror | Laravel writes Postgres, then upserts Express `store.json` via `/internal/tickets/upsert` |
| Health | `phase: 8`, `slice: 5` |

### Verify

```powershell
docker compose -f docker/compose.yml -f docker/compose.override.yml up --build -d nginx api web
docker compose -f docker/compose.yml -f docker/compose.override.yml up -d --force-recreate nginx
docker compose -f docker/compose.yml -f docker/compose.override.yml exec api php artisan rms:smoke-slice8-comment-edit
curl.exe -sI http://localhost:8080/dept/tickets/RISK-SMOKE-AAAAAA/comment/edit
curl.exe -sI http://localhost:8080/supervisor/tickets/RISK-SMOKE-AAAAAA/comment/react
```

Expected: guest URLs → Laravel `302` to `/login` or `405 Allow: POST` (no `X-Powered-By: Express`).

### Opt out

```powershell
docker compose -f docker/compose.yml -f docker/compose.override.yml -f docker/compose.soak.yml up -d nginx api web
```

## Phase 8 slice 4 (Dept personnel + resolution)

| Piece | Notes |
|-------|--------|
| nginx `POST /dept/tickets/:ref/{personnel,resolution}` | Laravel personnel assign + resolution (alias of close) |
| `USE_LARAVEL_DEPT_TICKET_MUTATIONS` | Compose default **`true`** (same flag as workflow/comment/documents) |
| Express mirror | Laravel writes Postgres, then upserts Express `store.json` via `/internal/tickets/upsert` |
| Health | `phase: 8`, `slice: 4` |

### Verify

```powershell
docker compose -f docker/compose.yml -f docker/compose.override.yml up --build -d nginx api web
docker compose -f docker/compose.yml -f docker/compose.override.yml up -d --force-recreate nginx
docker compose -f docker/compose.yml -f docker/compose.override.yml exec api php artisan rms:smoke-slice8-dept-personnel
curl.exe -sI http://localhost:8080/dept/tickets/RISK-SMOKE-AAAAAA/personnel
```

Expected: guest personnel URL → Laravel `302` to `/login` or `405 Allow: POST` (no `X-Powered-By: Express`).

### Opt out

```powershell
docker compose -f docker/compose.yml -f docker/compose.override.yml -f docker/compose.soak.yml up -d nginx api web
```

## Phase 8 slice 3 (Dept documents)

| Piece | Notes |
|-------|--------|
| nginx `POST /dept/tickets/:ref/documents` | Laravel multipart dept documents (CSRF, MinIO + Postgres) |
| `USE_LARAVEL_DEPT_TICKET_MUTATIONS` | Compose default **`true`** (same flag as workflow/comment) |
| Express mirror | Laravel writes Postgres + MinIO, then upserts Express `store.json` via `/internal/tickets/upsert` |
| Health | `phase: 8`, `slice: 3` |

### Verify

```powershell
docker compose -f docker/compose.yml -f docker/compose.override.yml up --build -d nginx api web
docker compose -f docker/compose.yml -f docker/compose.override.yml up -d --force-recreate nginx
docker compose -f docker/compose.yml -f docker/compose.override.yml exec api php artisan rms:smoke-slice8-dept-documents
curl.exe -sI http://localhost:8080/dept/tickets/RISK-SMOKE-AAAAAA/documents
```

Expected: guest documents URL → Laravel `302` to `/login` or `405 Allow: POST` (no `X-Powered-By: Express`).

### Opt out

```powershell
docker compose -f docker/compose.yml -f docker/compose.override.yml -f docker/compose.soak.yml up -d nginx api web
```

## Phase 8 slice 2 (Reporter evidence + accomplishment)

| Piece | Notes |
|-------|--------|
| nginx `POST /supervisor/tickets/:ref/{evidence,accomplishment}` | Laravel multipart evidence + accomplishment (CSRF, MinIO + Postgres) |
| `USE_LARAVEL_REPORTER_UPLOAD_MUTATIONS` | Compose default **`true`** (same flag as create/edit) |
| Express mirror | Laravel writes Postgres + MinIO + accomplishment row, then upserts Express `store.json` |
| Health | `phase: 8`, `slice: 2` |

### Verify

```powershell
docker compose -f docker/compose.yml -f docker/compose.override.yml exec api php artisan rms:smoke-slice8-reporter-evidence
curl.exe -sI http://localhost:8080/supervisor/tickets/RISK-SMOKE-AAAAAA/evidence
```

Expected: guest evidence URL → Laravel `302` to `/login` or `405 Allow: POST` (no `X-Powered-By: Express`).

## Phase 8 slice 1 (Reporter create/edit uploads)

| Piece | Notes |
|-------|--------|
| nginx `POST /supervisor/tickets/new/preview` + `POST /supervisor/tickets/:ref/edit` | Laravel multipart create/edit (CSRF, MinIO + Postgres) |
| `USE_LARAVEL_REPORTER_UPLOAD_MUTATIONS` | Compose default **`true`** |
| Express mirror | Laravel writes Postgres + MinIO, then upserts Express `store.json` via `/internal/tickets/upsert` |
| Still Express | Evidence/accomplishment/documents (moved in slices 2–3); comment edit/react |
| Health | `phase: 8`, `slice: 1` |

### Verify

```powershell
docker compose -f docker/compose.yml -f docker/compose.override.yml up --build -d nginx api web
docker compose -f docker/compose.yml -f docker/compose.override.yml up -d --force-recreate nginx
docker compose -f docker/compose.yml -f docker/compose.override.yml exec api php artisan rms:smoke-slice8-reporter-uploads
curl.exe -sI http://localhost:8080/supervisor/tickets/new/preview
```

Expected: guest preview URL → Laravel `302` to `/login` or `405 Allow: POST` (no `X-Powered-By: Express`).

### Opt out

```powershell
docker compose -f docker/compose.yml -f docker/compose.override.yml -f docker/compose.soak.yml up -d nginx api web
```

## Phase 7 slice 13 (Dept thread-comment)

| Piece | Notes |
|-------|--------|
| nginx `POST /dept/tickets/:ref/comment` | Laravel dept thread comment + reply (CSRF, text only) |
| `USE_LARAVEL_DEPT_TICKET_MUTATIONS` | Compose default **`true`** (same flag as workflow) |
| Express mirror | Laravel writes Postgres, then upserts Express `store.json` via `/internal/tickets/upsert` |
| Still Express | Reporter create/edit uploads; evidence/accomplishment/documents; comment edit/react |
| Health | `phase: 7`, `slice: 13` |

### Verify

```powershell
docker compose -f docker/compose.yml -f docker/compose.override.yml up -d nginx api web
docker compose -f docker/compose.yml -f docker/compose.override.yml exec api php artisan rms:smoke-slice7-dept-thread-comments
curl.exe -sI http://localhost:8080/dept/tickets/RISK-SMOKE-AAAAAA/comment
```

Expected: guest comment URL → Laravel `302` to `/login` or `405 Allow: POST` (no `X-Powered-By: Express`).

### Opt out

```powershell
docker compose -f docker/compose.yml -f docker/compose.override.yml -f docker/compose.soak.yml up -d nginx api web
```

## Phase 7 slice 12 (President comment)

| Piece | Notes |
|-------|--------|
| nginx `POST /president/tickets/:ref/comment` | Laravel president thread comment (CSRF, text only, High/Critical) |
| `USE_LARAVEL_PRESIDENT_TICKET_MUTATIONS` | Compose default **`true`** (same flag as decision) |
| Express mirror | Laravel writes Postgres, then upserts Express `store.json` via `/internal/tickets/upsert` |
| Still Express | Reporter create/edit uploads; dept thread-comments; evidence/accomplishment/documents |
| Health | `phase: 7`, `slice: 12` |

### Verify

```powershell
docker compose -f docker/compose.yml -f docker/compose.override.yml up -d nginx api web
docker compose -f docker/compose.yml -f docker/compose.override.yml exec api php artisan rms:smoke-slice7-president-thread-comments
curl.exe -sI http://localhost:8080/president/tickets/RISK-SMOKE-AAAAAA/comment
```

Expected: guest comment URL → Laravel `302` to `/login` or `405 Allow: POST` (no `X-Powered-By: Express`).

### Opt out

```powershell
docker compose -f docker/compose.yml -f docker/compose.override.yml -f docker/compose.soak.yml up -d nginx api web
```

## Phase 7 slice 11 (Officer thread-comment)

| Piece | Notes |
|-------|--------|
| nginx `POST /officer/tickets/:ref/thread-comment` | Laravel RMO governance comment + reply (CSRF, text only) |
| `USE_LARAVEL_OFFICER_TICKET_MUTATIONS` | Compose default **`true`** (same flag as reopen) |
| Express mirror | Laravel writes Postgres, then upserts Express `store.json` via `/internal/tickets/upsert` |
| Still Express | Reporter create/edit uploads; president/dept thread-comments; evidence/accomplishment/documents |
| Health | `phase: 7`, `slice: 11` |

### Verify

```powershell
docker compose -f docker/compose.yml -f docker/compose.override.yml up -d nginx api web
docker compose -f docker/compose.yml -f docker/compose.override.yml exec api php artisan rms:smoke-slice7-officer-thread-comments
curl.exe -sI http://localhost:8080/officer/tickets/RISK-SMOKE-AAAAAA/thread-comment
```

Expected: guest thread-comment URL → Laravel `302` to `/login` or `405 Allow: POST` (no `X-Powered-By: Express`).

### Opt out

```powershell
docker compose -f docker/compose.yml -f docker/compose.override.yml -f docker/compose.soak.yml up -d nginx api web
```

## Phase 7 slice 10 (Executive comment)

| Piece | Notes |
|-------|--------|
| nginx `POST /executive/tickets/:ref/comment` | Laravel thread comment + reply (CSRF, text only) |
| `USE_LARAVEL_EXECUTIVE_TICKET_MUTATIONS` | Compose default **`true`** |
| Express mirror | Laravel writes Postgres, then upserts Express `store.json` via `/internal/tickets/upsert` |
| Still Express | Reporter create/edit uploads; officer/president/dept thread-comments; evidence/accomplishment/documents |
| Health | `phase: 7`, `slice: 10` |

### Verify

```powershell
docker compose -f docker/compose.yml -f docker/compose.override.yml up -d nginx api web
docker compose -f docker/compose.yml -f docker/compose.override.yml exec api php artisan rms:smoke-slice7-executive-ticket-mutations
curl.exe -sI http://localhost:8080/executive/tickets/RISK-SMOKE-AAAAAA/comment
```

Expected: guest comment URL → Laravel `302` to `/login` or `405 Allow: POST` (no `X-Powered-By: Express`).

### Opt out

```powershell
docker compose -f docker/compose.yml -f docker/compose.override.yml -f docker/compose.soak.yml up -d nginx api web
```

## Phase 7 slice 9 (President decision)

| Piece | Notes |
|-------|--------|
| nginx `POST /president/tickets/:ref/decision` | Laravel action-plan + final decision (CSRF) |
| `USE_LARAVEL_PRESIDENT_TICKET_MUTATIONS` | Compose default **`true`** |
| Express mirror | Laravel writes Postgres, then upserts Express `store.json` via `/internal/tickets/upsert` |
| Still Express | President thread-comment (uploads) |
| Health | `phase: 7`, `slice: 9` |

### Verify

```powershell
docker compose -f docker/compose.yml -f docker/compose.override.yml up -d nginx api web
docker compose -f docker/compose.yml -f docker/compose.override.yml exec api php artisan rms:smoke-slice7-president-ticket-mutations
curl.exe -sI http://localhost:8080/president/tickets/RISK-SMOKE-AAAAAA/decision
```

Expected: guest decision URL → Laravel `302` to `/login` or `405 Allow: POST` (no `X-Powered-By: Express`).

### Opt out

```powershell
docker compose -f docker/compose.yml -f docker/compose.override.yml -f docker/compose.soak.yml up -d nginx api web
```

## Phase 7 slice 8 (Officer reopen)

| Piece | Notes |
|-------|--------|
| nginx `POST /officer/tickets/:ref/reopen` | Laravel reopen + reassign department (CSRF) |
| `USE_LARAVEL_OFFICER_TICKET_MUTATIONS` | Compose default **`true`** |
| Express mirror | Laravel writes Postgres, then upserts Express `store.json` via `/internal/tickets/upsert` |
| Still Express | Officer thread-comment (uploads) |
| Health | `phase: 7`, `slice: 8` |

### Verify

```powershell
docker compose -f docker/compose.yml -f docker/compose.override.yml up -d nginx api web
docker compose -f docker/compose.yml -f docker/compose.override.yml exec api php artisan rms:smoke-slice7-officer-ticket-mutations
curl.exe -sI http://localhost:8080/officer/tickets/RISK-SMOKE-AAAAAA/reopen
```

Expected: guest reopen URL → Laravel `302` to `/login` or `405 Allow: POST` (no `X-Powered-By: Express`).

### Opt out

```powershell
docker compose -f docker/compose.yml -f docker/compose.override.yml -f docker/compose.soak.yml up -d nginx api web
```

## Phase 7 slice 7 (Reporter preview/delete mutations)

| Piece | Notes |
|-------|--------|
| nginx `POST /supervisor/tickets/new/preview/:ref/{save,submit}` | Laravel preview save (no-op persist) + submit (CSRF confirm) |
| nginx `POST /supervisor/tickets/:ref/delete` | Laravel draft hard-delete (CSRF) |
| `USE_LARAVEL_REPORTER_TICKET_MUTATIONS` | Compose default **`true`** |
| Express mirror | Submit upserts `store.json` via `/internal/tickets/upsert`; delete removes draft via `/internal/tickets/delete-draft` |
| Still Express | Multipart create/edit, evidence, accomplishment, comments |
| Health | `phase: 7`, `slice: 7` |

### Verify

```powershell
docker compose -f docker/compose.yml -f docker/compose.override.yml up -d nginx api web
docker compose -f docker/compose.yml -f docker/compose.override.yml exec api php artisan rms:smoke-slice7-reporter-ticket-mutations
curl.exe -sI http://localhost:8080/supervisor/tickets/RISK-SMOKE-AAAAAA/delete
```

Expected: guest delete URL → Laravel `302` to `/login` or `405 Allow: POST` (no `X-Powered-By: Express`).

### Opt out

```powershell
docker compose -f docker/compose.yml -f docker/compose.override.yml -f docker/compose.soak.yml up -d nginx api web
```

## Phase 7 slice 6 (Dept ticket workflow mutations)

| Piece | Notes |
|-------|--------|
| nginx `POST /dept/tickets/:ref/{accept,reject,return,reassign,action-plan,close}` | Laravel workflow (CSRF); personnel/documents/comments/resolution stay Express |
| `USE_LARAVEL_DEPT_TICKET_MUTATIONS` | Compose default **`true`** |
| Express mirror | Laravel writes Postgres, then upserts Express `store.json` via `/internal/tickets/upsert` |
| Health | `phase: 7`, `slice: 6` |

### Verify

```powershell
docker compose -f docker/compose.yml -f docker/compose.override.yml up -d nginx api web
docker compose -f docker/compose.yml -f docker/compose.override.yml exec api php artisan rms:smoke-slice7-dept-ticket-mutations
curl.exe -sI http://localhost:8080/dept/tickets/RISK-SMOKE-AAAAAA/accept
```

Expected: guest accept URL → Laravel `302` to `/login` or `405 Allow: POST` (no `X-Powered-By: Express`).

### Opt out

```powershell
docker compose -f docker/compose.yml -f docker/compose.override.yml -f docker/compose.soak.yml up -d nginx api web
```

## Phase 7 slice 5 (Admin ticket soft-delete)

| Piece | Notes |
|-------|--------|
| nginx `POST /admin/tickets/:ref/delete` | Laravel soft-delete with reason (CSRF) |
| `USE_LARAVEL_ADMIN_TICKET_MUTATIONS` | Compose default **`true`** |
| Express mirror | Laravel writes Postgres, then mirrors into Express `store.json` via `/internal/tickets/soft-delete` |
| Health | `phase: 7`, `slice: 5` |

### Verify

```powershell
docker compose -f docker/compose.yml -f docker/compose.override.yml up -d nginx api web
docker compose -f docker/compose.yml -f docker/compose.override.yml exec api php artisan rms:smoke-slice7-admin-ticket-mutations
curl.exe -sI http://localhost:8080/admin/tickets/RISK-SMOKE-AAAAAA/delete
```

Expected: guest delete URL → Laravel `302` to `/login` (no `X-Powered-By: Express`).

### Opt out

```powershell
docker compose -f docker/compose.yml -f docker/compose.override.yml -f docker/compose.soak.yml up -d nginx api web
```

## Phase 7 slice 4 (Admin settings mutations)

| Piece | Notes |
|-------|--------|
| nginx `POST /admin/settings*` | Laravel save / reset-landing / reset-ai (CSRF) |
| `USE_LARAVEL_ADMIN_SETTINGS_MUTATIONS` | Compose default **`true`** |
| Express mirror | Laravel writes Postgres `system_settings`, then mirrors into Express `store.json` via `/internal/org/settings` |
| Health | `phase: 7`, `slice: 4` |

### Verify

```powershell
docker compose -f docker/compose.yml -f docker/compose.override.yml up -d nginx api web
docker compose -f docker/compose.yml -f docker/compose.override.yml exec api php artisan migrate --force
docker compose -f docker/compose.yml -f docker/compose.override.yml exec api php artisan rms:smoke-slice7-admin-settings-mutations
curl.exe -sI http://localhost:8080/admin/settings
```

Expected: guest `/admin/settings` → Laravel `302` to `/login` (no `X-Powered-By: Express`).

### Opt out

```powershell
docker compose -f docker/compose.yml -f docker/compose.override.yml -f docker/compose.soak.yml up -d nginx api web
```

## Phase 7 slice 3 (Admin user mutations)

| Piece | Notes |
|-------|--------|
| nginx `POST /admin/users*` | Laravel create/edit/activate/deactivate/delete/reset-password (CSRF) |
| `USE_LARAVEL_ADMIN_USER_MUTATIONS` | Compose default **`true`** |
| Express mirror | Laravel writes Postgres, then mirrors into Express `store.json` via `/internal/org/users` (plaintext password for Express fallback) |
| Health | `phase: 7`, `slice: 3` |

### Verify

```powershell
docker compose -f docker/compose.yml -f docker/compose.override.yml up -d nginx api web
docker compose -f docker/compose.yml -f docker/compose.override.yml exec api php artisan rms:smoke-slice7-admin-user-mutations
curl.exe -sI http://localhost:8080/admin/users
```

Expected: guest `/admin/users` → Laravel `302` to `/login` (no `X-Powered-By: Express`).

### Opt out

```powershell
docker compose -f docker/compose.yml -f docker/compose.override.yml -f docker/compose.soak.yml up -d nginx api web
```

## Phase 7 slice 2 (Admin position mutations)

| Piece | Notes |
|-------|--------|
| nginx `POST /admin/positions*` | Laravel create/edit/delete (CSRF) |
| `USE_LARAVEL_ADMIN_POS_MUTATIONS` | Compose default **`true`** |
| Express mirror | Laravel writes Postgres, then mirrors into Express `store.json` via `/internal/org/positions` |
| Health | `phase: 7`, `slice: 2` |

### Verify

```powershell
docker compose -f docker/compose.yml -f docker/compose.override.yml up -d nginx api web
docker compose -f docker/compose.yml -f docker/compose.override.yml exec api php artisan rms:smoke-slice7-admin-pos-mutations
curl.exe -sI http://localhost:8080/admin/positions
```

Expected: guest `/admin/positions` → Laravel `302` to `/login` (no `X-Powered-By: Express`).

### Opt out

```powershell
docker compose -f docker/compose.yml -f docker/compose.override.yml -f docker/compose.soak.yml up -d nginx api web
```

## Phase 7 slice 1 (Admin department mutations)

| Piece | Notes |
|-------|--------|
| nginx `POST /admin/departments*` | Laravel create/edit/delete (CSRF) |
| `USE_LARAVEL_ADMIN_DEPT_MUTATIONS` | Compose default **`true`** |
| Express mirror | Laravel writes Postgres, then mirrors into Express `store.json` via `/internal/org/departments` |
| Health | `phase: 7`, `slice: 1` |

### Verify

```powershell
docker compose -f docker/compose.yml -f docker/compose.override.yml up -d nginx api web
docker compose -f docker/compose.yml -f docker/compose.override.yml exec api php artisan rms:smoke-slice7-admin-dept-mutations
curl.exe -sI -X POST http://localhost:8080/admin/departments
```

Expected: guest POST → Laravel `302` to `/login` (XSRF cookie, no `X-Powered-By: Express`).

### Opt out

```powershell
docker compose -f docker/compose.yml -f docker/compose.override.yml -f docker/compose.soak.yml up -d nginx api web
```

## Migration complete (Express → Laravel)

The Express → Laravel cutover is finished. Next.js at `/app` now covers reporter create/submit, role workflow mutations, and admin users/settings/audit. Blade remains a parallel session console. Remaining optional work:

1. GPU/ONNX model swap for ai-service
2. Dual-write `USE_LARAVEL_INTERNAL_*` flags stay off unless you re-enable `store.json` mirroring

## Related

- [ARCHITECTURE.md](ARCHITECTURE.md)
- [DOCKER.md](DOCKER.md)
- [ENVIRONMENT.md](ENVIRONMENT.md)
- [LOGIN.md](LOGIN.md)
- [ADR 001](adr/001-backend-laravel.md)
