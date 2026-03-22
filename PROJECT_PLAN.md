# FLOZ Project Plan

## Current State

FLOZ already has a strong product foundation as a web-first, multi-tenant LMS built around Laravel, Vue, and Inertia. The main application in `src` already covers platform administration, tenant dashboards, student and staff management, classes, subjects, schedules, grades, report cards, announcements, audit logs, meetings, and assignments.

The current maturity gap is not feature breadth. It is operational readiness. The most urgent work is around access-control hardening, deployment consistency, test coverage, and documentation accuracy.

## Architecture Snapshot

- Web entrypoints are centered in `src/routes/web.php`.
- Mobile/API entrypoints live in `src/routes/api.php`.
- Tenant resolution happens early through `src/app/Http/Middleware/IdentifyTenant.php` and `src/app/Http/Middleware/IdentifyTenantFromHeader.php`.
- Central platform logic is mainly in `src/app/Http/Controllers/Platform` and `src/app/Models/Central`.
- Tenant logic is mainly in `src/app/Http/Controllers/Tenant`, `src/app/Models/Tenant`, and `src/app/Services`.
- Vue/Inertia pages are organized under `src/resources/js/Pages/Platform` and `src/resources/js/Pages/Tenant`.

## Strategic Priorities

1. Fix platform versus tenant access boundaries.
2. Make deployment and environment setup consistent and reproducible.
3. Add CI and high-value automated tests.
4. Align product docs, setup docs, and API docs with reality.
5. Narrow and finish a real mobile MVP.
6. Resume feature expansion only after the foundation is stable.

## Risk-Based Roadmap

### Phase 1 - Access Control and Safety

Goals:
- Prevent tenant-context users from reaching platform-only routes.
- Make domain/context rules explicit in middleware instead of implicit in login flow.
- Add regression coverage for platform-only endpoints.

Key actions:
- Introduce dedicated platform middleware for host and role checks.
- Apply it to the `/platform` route group in `src/routes/web.php`.
- Review `src/app/Http/Controllers/Platform/TenantController.php` and `src/app/Http/Controllers/Platform/DashboardController.php` for assumptions about authenticated user type.
- Add denial tests for tenant users hitting platform routes.

Success criteria:
- Only central super admins can access `/platform/*`.
- Tenant users always receive a deny/redirect outcome on platform routes.
- Tests exist for both allowed and denied cases.

### Phase 2 - Deployment and Configuration Cleanup

Goals:
- Remove drift between docs, env templates, and runtime configuration.
- Create one dependable deployment path.

Key actions:
- Align `README.md`, `.env.example`, `docker-compose.yml`, `docker-compose.dev.yml`, and `src/config/database.php`.
- Decide whether Docker is the canonical local path, production path, or both.
- Build a self-contained production image instead of relying on mounted host state.
- Verify nginx, app, DB naming, and TLS port assumptions end to end.

Success criteria:
- A new developer can boot the stack from one documented path.
- Production build artifacts are generated from the image build, not from local machine state.
- Central and tenant DB naming conventions are documented and consistent.

### Phase 3 - Test Baseline and CI

Goals:
- Establish a safety net for core multi-tenant flows.
- Catch regressions before deployment.

Key actions:
- Add CI workflow for backend and frontend sanity checks.
- Start backend feature tests for auth, tenancy resolution, platform route protection, tenant dashboards, and report cards.
- Add focused API tests for mobile auth and tenant-aware headers.
- Keep the first wave small but high value.

Success criteria:
- CI runs automatically on pushes and pull requests.
- Critical access-control and tenancy behaviors have regression tests.
- Mobile auth and a few key API paths are verified automatically.

### Phase 4 - Documentation and Contract Alignment

Goals:
- Reduce confusion between aspirational docs and current implementation.
- Make docs safe for engineering, onboarding, and deployment.

Key actions:
- Replace stale/default READMEs in `src/README.md` and `floz_mobile/README.md`.
- Keep `README.md` accurate about actual production readiness.
- Regenerate OpenAPI output from current routes/controllers.
- Document central domain, tenant domain, subdomain, and custom-domain behavior clearly.

Success criteria:
- One setup guide matches the working commands.
- API docs reflect current endpoints.
- Mobile and web teams can rely on the same source of truth.

### Phase 5 - Mobile MVP Completion

Goals:
- Ship a smaller but real mobile scope.
- Stop over-promising unfinished app/API surfaces.

Key actions:
- Move the API base URL in `floz_mobile/lib/core/constants/api_constants.dart` into env/flavor config.
- Confirm which mobile screens are truly backed by working APIs.
- Finish auth, dashboard, schedule, announcements, and one assignment flow before broader expansion.
- Defer parent features until parent data linking is real.

Success criteria:
- Mobile can connect to non-local environments cleanly.
- Every shipped mobile screen has a stable API contract.
- Placeholder flows are either finished or explicitly removed from scope.

### Phase 6 - Product Expansion

Goals:
- Resume roadmap delivery from a stable base.

Key actions:
- Improve parent experience.
- Add analytics and operational reporting.
- Strengthen onboarding and platform operations.
- Polish UX and performance in high-traffic tenant workflows.

Success criteria:
- New features land on top of tested, documented, deployable foundations.

## Immediate Top 10 Tasks

1. Add platform-only middleware and apply it to `/platform/*`.
2. Add feature tests proving tenant users cannot access platform pages.
3. Review auth provider/model switching for tenant and central contexts.
4. Reconcile DB naming and env assumptions across docs and config.
5. Build a reproducible production Docker image.
6. Add a first CI workflow.
7. Add tenancy and login smoke tests.
8. Replace stale/default app READMEs.
9. Regenerate and verify API docs.
10. Re-scope mobile into a smaller, deliverable MVP.

## Recommended Delivery Order

- First 2 weeks: security boundary and deployment consistency.
- Next 2 weeks: CI, tenancy tests, auth tests, and report-card smoke coverage.
- Following 2 weeks: documentation reset and mobile MVP contract cleanup.
- After that: finish mobile MVP and then reopen feature expansion.

## Effort View

- Foundation hardening only: medium effort.
- Hardening plus deployment cleanup plus CI: medium to large effort.
- Hardening plus mobile MVP completion: large effort.
