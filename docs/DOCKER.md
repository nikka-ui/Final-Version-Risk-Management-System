# Docker Guide

Run the RMS stack locally or in production using Docker Compose.

## Prerequisites

- Docker Engine 24+ and Docker Compose v2
- Git clone of this repository
- Ports available per [Port Registry](PORT_REGISTRY.md) (default: `8080`)

## First-time setup

### 1. Environment file

```powershell
cd C:\dev\Version4-Risk-Management-System
Copy-Item .env.example .env
```

### 2. Docker secrets

```powershell
New-Item -ItemType Directory -Force -Path docker\secrets
Copy-Item docker\secrets\db_password.txt.example docker\secrets\db_password.txt
Copy-Item docker\secrets\app_key.txt.example docker\secrets\app_key.txt
# Edit both files with strong random values before production
```

Secret files are gitignored. Compose reads them from `docker/secrets/`.

### 3. Start development stack

From repository root:

```powershell
docker compose -f docker/compose.yml -f docker/compose.override.yml up --build -d
```

Optional dev tools (MinIO, Mailpit):

```powershell
docker compose -f docker/compose.yml -f docker/compose.override.yml --profile dev up -d
```

### 4. Verify health

```powershell
curl http://localhost:8080/health
curl http://localhost:8080/ai-health
curl http://localhost:8080/
```

Expected: JSON `{"status":"ok"}` from `/health` and `/ai-health`.

## Compose file layout

| File | Purpose |
|------|---------|
| [`docker/compose.yml`](../docker/compose.yml) | Base services, networks, volumes, secrets |
| [`docker/compose.override.yml`](../docker/compose.override.yml) | Dev: localhost DB/AI ports, optional profiles |
| [`docker/compose.prod.yml`](../docker/compose.prod.yml) | Prod: resource limits, read-only roots, 80/443 |

## Development vs production

| Mode | Command |
|------|---------|
| **Development** | `docker compose -f docker/compose.yml -f docker/compose.override.yml up -d` (Laravel Blade + API) |
| **Blade login** | Phase 5 slice 4: edge `/laravel/` → Laravel; Express `/login` redirects when `USE_LARAVEL_LOGIN_UI=true` |
| **Blade admin profile** | Phase 5 slice 5: `/admin/profile` → `/laravel/admin/profile` when `USE_LARAVEL_PROFILE_UI=true` |
| **Blade reporter profile** | Phase 5 slice 6: `/supervisor/profile` → `/laravel/supervisor/profile` when `USE_LARAVEL_REPORTER_PROFILE_UI=true` |
| **Blade reporter dashboard** | Phase 5 slice 7: `/supervisor` → `/laravel/supervisor` when `USE_LARAVEL_REPORTER_DASHBOARD_UI=true` |
| **Blade reporter ticket lists** | Phase 5 slice 8: `/supervisor/tickets` (+ drafts/submitted/returned/overdue) → Laravel when `USE_LARAVEL_REPORTER_TICKETS_UI=true` |
| **Blade reporter ticket detail** | Phase 5 slice 9: `/supervisor/tickets/:ref` → `/laravel/supervisor/tickets/:ref` when `USE_LARAVEL_REPORTER_TICKET_DETAIL_UI=true` |
| **Blade reporter notifications** | Phase 5 slice 10: `/supervisor/notifications` → `/laravel/supervisor/notifications` when `USE_LARAVEL_REPORTER_NOTIFICATIONS_UI=true` |
| **Blade reporter accomplishments** | Phase 5 slice 11: `/supervisor/accomplishments` → `/laravel/supervisor/accomplishments` when `USE_LARAVEL_REPORTER_ACCOMPLISHMENTS_UI=true` |
| **Blade reporter actions** | Phase 5 slice 12: `/supervisor/actions` → `/laravel/supervisor/actions` when `USE_LARAVEL_REPORTER_ACTIONS_UI=true` |
| **Blade reporter ticket forms** | Phase 5 slice 13: create/edit/preview GETs → `/laravel/supervisor/tickets/...` when `USE_LARAVEL_REPORTER_TICKET_FORM_UI=true` |
| **Blade admin dashboard** | Phase 5 slice 14: `GET /admin` → `/laravel/admin` when `USE_LARAVEL_ADMIN_DASHBOARD_UI=true` |
| **Blade admin users** | Phase 5 slice 15: `GET /admin/users` → `/laravel/admin/users` when `USE_LARAVEL_ADMIN_USERS_UI=true` |
| **Blade admin departments** | Phase 5 slice 16: `GET /admin/departments` → `/laravel/admin/departments` when `USE_LARAVEL_ADMIN_DEPARTMENTS_UI=true` |
| **Blade admin positions** | Phase 5 slice 17: `GET /admin/positions` → `/laravel/admin/positions` when `USE_LARAVEL_ADMIN_POSITIONS_UI=true` |
| **Blade admin tickets** | Phase 5 slice 18: `GET /admin/tickets` → `/laravel/admin/tickets` when `USE_LARAVEL_ADMIN_TICKETS_UI=true` |
| **Blade admin ticket detail** | Phase 5 slice 19: `GET /admin/tickets/:ref` → `/laravel/admin/tickets/:ref` when `USE_LARAVEL_ADMIN_TICKET_DETAIL_UI=true` |
| **Blade admin audit logs** | Phase 5 slice 20 + Phase 8 slice 7: `GET /admin/audit-logs` + `GET /admin/audit-logs/export` → Laravel when `USE_LARAVEL_ADMIN_AUDIT_LOGS_UI=true` |
| **Blade admin settings** | Phase 5 slice 21: `GET /admin/settings` → `/laravel/admin/settings` when `USE_LARAVEL_ADMIN_SETTINGS_UI=true` |
| **Blade dept dashboard** | Phase 5 slice 22: `GET /dept` → `/laravel/dept` when `USE_LARAVEL_DEPT_DASHBOARD_UI=true` |
| **Blade dept queues** | Phase 5 slice 23: `GET /dept/{inbox,…,tickets}` → `/laravel/dept/…` when `USE_LARAVEL_DEPT_QUEUES_UI=true` |
| **Blade dept ticket detail** | Phase 5 slice 24: `GET /dept/tickets/:ref` → `/laravel/dept/tickets/:ref` when `USE_LARAVEL_DEPT_TICKET_DETAIL_UI=true` |
| **Blade officer dashboard** | Phase 5 slice 25: `GET /officer` → `/laravel/officer` when `USE_LARAVEL_OFFICER_DASHBOARD_UI=true` |
| **Blade officer queues** | Phase 5 slice 26: `GET /officer/{tickets,overdue,monitoring,action-plans}` → `/laravel/officer/…` when `USE_LARAVEL_OFFICER_QUEUES_UI=true` |
| **Blade officer ticket detail** | Phase 5 slice 27: `GET /officer/tickets/:ref` → `/laravel/officer/tickets/:ref` when `USE_LARAVEL_OFFICER_TICKET_DETAIL_UI=true` |
| **Blade officer aliases** | Phase 6 slice 5: `GET /officer/{ai-review,review,final-validation}` → Laravel aliases when `USE_LARAVEL_OFFICER_ALIASES_UI=true` |
| **Blade employee dashboard** | Phase 6 slice 6: `GET /dashboard` → Laravel when `USE_LARAVEL_DASHBOARD_UI=true` |
| **Blade admin department mutations** | Phase 7 slice 1: `POST /admin/departments*` → Laravel when `USE_LARAVEL_ADMIN_DEPT_MUTATIONS=true` |
| **Blade admin position mutations** | Phase 7 slice 2: `POST /admin/positions*` → Laravel when `USE_LARAVEL_ADMIN_POS_MUTATIONS=true` |
| **Blade admin user mutations** | Phase 7 slice 3: `POST /admin/users*` → Laravel when `USE_LARAVEL_ADMIN_USER_MUTATIONS=true` |
| **Blade admin settings mutations** | Phase 7 slice 4: `POST /admin/settings*` → Laravel when `USE_LARAVEL_ADMIN_SETTINGS_MUTATIONS=true` |
| **Blade admin ticket mutations** | Phase 7 slice 5: `POST /admin/tickets/:ref/delete` → Laravel when `USE_LARAVEL_ADMIN_TICKET_MUTATIONS=true` |
| **Blade dept ticket mutations** | Phase 7 slice 6 + slice 13 + Phase 8 slice 3–5: `POST /dept/tickets/:ref/{accept,reject,return,reassign,action-plan,close,comment,documents,personnel,resolution,comment/edit,comment/react}` → Laravel when `USE_LARAVEL_DEPT_TICKET_MUTATIONS=true` |
| **Blade reporter ticket mutations** | Phase 7 slice 7 + Phase 8 slice 5: `POST /supervisor/tickets/new/preview/:ref/{save,submit}` + `POST /supervisor/tickets/:ref/{delete,comment,comment/edit,comment/react}` → Laravel when `USE_LARAVEL_REPORTER_TICKET_MUTATIONS=true` |
| **Blade reporter upload mutations** | Phase 8 slice 1–2: `POST /supervisor/tickets/new/preview` + `POST /supervisor/tickets/:ref/{edit,evidence,accomplishment}` → Laravel when `USE_LARAVEL_REPORTER_UPLOAD_MUTATIONS=true` |
| **Blade officer ticket mutations** | Phase 7 slice 8 + slice 11: `POST /officer/tickets/:ref/reopen` + `POST /officer/tickets/:ref/thread-comment` → Laravel when `USE_LARAVEL_OFFICER_TICKET_MUTATIONS=true` |
| **Blade president ticket mutations** | Phase 7 slice 9 + slice 12: `POST /president/tickets/:ref/decision` + `POST /president/tickets/:ref/comment` → Laravel when `USE_LARAVEL_PRESIDENT_TICKET_MUTATIONS=true` |
| **Blade executive ticket mutations** | Phase 7 slice 10: `POST /executive/tickets/:ref/comment` → Laravel when `USE_LARAVEL_EXECUTIVE_TICKET_MUTATIONS=true` |
| **Blade other-role notifications** | Phase 8 slice 6–8: `POST /{dept,officer,executive,president}/notifications/read-all` + `GET .../notifications/open/:id` → Laravel when the matching dashboard UI flag is true |
| **Blade role attachment downloads** | Phase 8 slice 9: `GET /{supervisor,dept,officer,executive,president}/attachments/:id` → Laravel when the matching ticket-detail UI flag is true |
| **Blade static assets** | Phase 9 slice 1: `GET /css/*` + `/img/*` → Laravel `public/` |
| **Blade login bridge** | Phase 9 slice 2: `GET /auth/bridge` → Laravel when `USE_LARAVEL_LOGIN_UI=true` |
| **Blade logout** | Phase 9 slice 3: `GET`/`POST /logout` → Laravel when `USE_LARAVEL_LOGIN_UI=true` |
| **Unmatched edge fallback** | Phase 9 slice 4: unmatched `location /` → Laravel (`/favicon.ico`, 404s). |
| **Ticket dual-write internals** | Phase 9 slice 5: `POST /internal/tickets/*` → Laravel `store.json` when `USE_LARAVEL_INTERNAL_TICKETS=true`. |
| **Org dual-write internals** | Phase 9 slice 6: `POST /internal/org/*` → Laravel `store.json` when `USE_LARAVEL_INTERNAL_ORG=true`. Default nginx `^~ /internal/` → Laravel. |
| **Retire Express web** | Phase 9 slice 7–8: `web` service and `docker/web` source removed. |
| **Blade executive dashboard** | Phase 5 slice 28: `GET /executive` → `/laravel/executive` when `USE_LARAVEL_EXECUTIVE_DASHBOARD_UI=true` |
| **Blade executive pages** | Phase 6 slice 3: `GET /executive/{heatmap,reports,trends,statistics,departments,register}` → Laravel when `USE_LARAVEL_EXECUTIVE_PAGES_UI=true` |
| **Blade executive ticket detail** | Phase 6 slice 4: `GET /executive/tickets/:ref` → Laravel when `USE_LARAVEL_EXECUTIVE_TICKET_DETAIL_UI=true` |
| **Blade president dashboard** | Phase 5 slice 29: `GET /president` → `/laravel/president` when `USE_LARAVEL_PRESIDENT_DASHBOARD_UI=true` |
| **Blade president ticket detail** | Phase 5 slice 31: `GET /president/tickets/:ref` → `/laravel/president/tickets/…` when `USE_LARAVEL_PRESIDENT_TICKET_DETAIL_UI=true` |
| **Blade president queues** | Phase 5 slice 30: `GET /president/{pending,high,critical,trends}` → `/laravel/president/…` when `USE_LARAVEL_PRESIDENT_QUEUES_UI=true` |
| **Edge `/` → Laravel** | Phase 6 slice 1: exact `GET /` → Laravel home redirect when `USE_LARAVEL_EDGE_ROOT=true` (POSTs stay Express) |
| **Unprefixed Blade UI** | Phase 6 slice 2–6: `GET /login`, `/dashboard`, `/admin`, `/supervisor`, `/dept`, `/officer`, `/president`, `/executive` → Laravel when `USE_LARAVEL_EDGE_UI=true` |
| **Production** | `docker compose -f docker/compose.yml -f docker/compose.prod.yml up -d` |

Production requires TLS certificates in `docker/nginx/certs/` (fullchain.pem, privkey.pem) and updated secrets.

## Service endpoints (development)

| URL | Service |
|-----|---------|
| http://localhost:8080/ | Laravel home redirect → `/login` or role console (Phase 6) |
| http://localhost:8080/login | Blade Sign In (Phase 6 slice 2) |
| http://localhost:8080/login | Sign-in — see [LOGIN.md](LOGIN.md) |
| http://localhost:8080/api/ | Laravel 11 API (Phase 1 identity; routes under `/api/v1`) |
| http://localhost:8080/health | nginx health |
| http://localhost:8080/ai-health | AI health (proxied) |
| http://127.0.0.1:5433 | PostgreSQL (host only) |
| http://127.0.0.1:5001 | AI service (direct debug) |
| http://127.0.0.1:8025 | Mailpit UI (`--profile dev`) |
| http://127.0.0.1:9001 | MinIO console (`--profile dev`) |

## Common commands

```powershell
# Validate compose configuration
docker compose -f docker/compose.yml -f docker/compose.override.yml config

# View logs
docker compose -f docker/compose.yml -f docker/compose.override.yml logs -f nginx api

# Stop and remove containers
docker compose -f docker/compose.yml -f docker/compose.override.yml down

# Stop and remove volumes (destructive)
docker compose -f docker/compose.yml -f docker/compose.override.yml down -v
```

## Application images

- **web** — Express RMS application on port 3000 (tickets, RBAC consoles, sessions)
- **api** — Laravel 11 + PHP 8.3-FPM + nginx on port 8080 ([ADR 001](adr/001-backend-laravel.md)); source in `backend/`
- **ai-service** — Flask service on port 5000

### Evolving the stack

#### Laravel API (Phase 1 — identity foundation)

Application code lives in [`backend/`](../backend/). The `api` image copies it and runs `composer install`. Express still owns `/` (login + role consoles). Details: [LARAVEL_MIGRATION.md](LARAVEL_MIGRATION.md).

```powershell
# Rebuild API after backend changes
docker compose -f docker/compose.yml -f docker/compose.override.yml up --build -d api

# Migrations (risk_attachments is create-if-missing; never dropped)
docker compose -f docker/compose.yml -f docker/compose.override.yml exec api php artisan migrate --force

# Import users from mounted store.json (read-only) into Postgres
docker compose -f docker/compose.yml -f docker/compose.override.yml exec api php artisan rms:import-users

# Smoke checks (Express still owns /)
curl http://localhost:8080/health
curl http://localhost:8080/api/v1/health
curl http://localhost:8080/api/v1
curl http://localhost:8080/login
curl http://localhost:8080/
```

Nginx still strips `/api` before proxying: public `/api/v1` → container `/v1`. Product UI/login are Laravel Blade (`/login`).

`store.json` is mounted into the API container at `/import/store.json` for import and dual-write (`docker/data/store.json`).

#### AI service

1. Phase 13 slice 1 ships a CPU transformer encoder (`transformer-hybrid-v1`) on top of taxonomy + TF-IDF, same `/classify` and `/summarize` JSON.
2. Optional GPU/ONNX model swap without changing Blade or API contracts.

#### Next.js frontend (Phase 14–15 — optional read UI)

1. Next.js 14 scaffold in `frontend/` (App Router, TypeScript, standalone Docker image).
2. Compose profile `frontend` publishes `127.0.0.1:3000` — Blade on `:8080` remains the live workflow edge.
3. nginx proxies `http://localhost:8080/app/` → frontend (`basePath: /app`).
4. Sanctum login, dashboard, tickets, reporter create/submit, notifications, departments at `/app/*`.
5. Smoke: `php artisan rms:smoke-phase15-migration` (alias: `rms:smoke-phase14-frontend`).
6. CI runs `npm run build` in `frontend/`.

### Ticket data reset

See [Operations — Resetting ticket data](OPERATIONS.md#resetting-ticket-data).

#### CI (Phase 12 slice 2 + Phase 15 slice 1)

- Push/PR runs [`.github/workflows/ci.yml`](../.github/workflows/ci.yml): Laravel PHPUnit, ai-service unit tests, Next.js `npm run build`, and Trivy scans of `rms-api`, `rms-ai-service`, `rms-frontend`, and `nginx:1.30-alpine`.
- Trivy **fails the job** on fixable **CRITICAL** CVEs; CRITICAL/HIGH are logged and uploaded as SARIF.
- Local tests: `cd backend && composer test`

## Troubleshooting

| Issue | Action |
|-------|--------|
| `secret file not found` | Create `docker/secrets/db_password.txt` and `app_key.txt` |
| Port 8080 in use | Set `NGINX_HTTP_PORT` in `.env` |
| `rms_data` network unreachable | Ensure `postgres` and `redis` are healthy: `docker compose ps` |
| API 502 via nginx | Wait for `api` healthcheck; check `docker compose logs api` |
| Login / app 502 after rebuild | nginx may have cached an old `web` IP. Config uses Docker DNS (`resolver 127.0.0.11`) with dynamic upstreams in `docker/nginx/conf.d/rms.conf`. Reload or restart nginx: `docker restart rms-nginx` |

## Related

- [Port Registry](PORT_REGISTRY.md)
- [Container Security](CONTAINER_SECURITY.md)
- [Environment Variables](ENVIRONMENT.md)
