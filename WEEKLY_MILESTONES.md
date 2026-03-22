# FLOZ Weekly Milestones

## Planning Assumptions

- Horizon: 8 weeks
- Team shape: 1-3 engineers
- Goal: make FLOZ safer, more reproducible, and closer to a real release baseline

## Week 1 - Lock Access Boundaries

Primary outcomes:
- Audit platform and tenant route protection.
- Implement dedicated platform-only middleware.
- Apply middleware to `/platform/*` routes.

Deliverables:
- Middleware for platform host and super-admin enforcement.
- Route updates in `src/routes/web.php`.
- Short security note describing central vs tenant access rules.

## Week 2 - Prove Access Rules with Tests

Primary outcomes:
- Add feature tests for platform denial from tenant context.
- Add allow-path tests for super admin on platform domain.
- Verify login redirects still behave correctly.

Deliverables:
- New auth and route-protection tests.
- Regression coverage for tenant and central login scenarios.

## Week 3 - Clean Deployment Story

Primary outcomes:
- Reconcile Docker and env assumptions.
- Decide the canonical local/dev/prod path.
- Remove host-state dependency from production setup.

Deliverables:
- Updated deployment notes.
- Revised Docker strategy.
- Verified DB naming and runtime conventions.

## Week 4 - Add CI and Core Smoke Checks

Primary outcomes:
- Add automated backend test execution.
- Add basic frontend/build verification.
- Ensure main branches get continuous checks.

Deliverables:
- CI workflow.
- Passing smoke test suite.
- Documented verification commands.

## Week 5 - Cover Tenancy and Academic Core

Primary outcomes:
- Add tests for tenancy resolution and tenant isolation.
- Add smoke coverage for dashboards, grades, and report cards.
- Verify tenant-specific model usage does not leak central context.

Deliverables:
- High-value backend feature tests.
- Short test matrix for critical modules.

## Week 6 - Reset Documentation

Primary outcomes:
- Replace stale/default READMEs.
- Align setup docs with actual commands.
- Regenerate API docs and verify health/check endpoints.

Deliverables:
- Updated `README.md` positioning.
- Rewritten `src/README.md` and `floz_mobile/README.md`.
- Current API contract docs.

## Week 7 - Finish Mobile MVP Contract

Primary outcomes:
- Freeze the first mobile release scope.
- Move mobile API base URL to env/flavor config.
- Verify login, dashboard, schedule, and announcement paths end to end.

Deliverables:
- Mobile MVP scope sheet.
- Updated environment-driven mobile config.
- API checklist for shipped screens.

## Week 8 - Release Readiness Review

Primary outcomes:
- Re-check security, deployment, docs, and test baseline.
- Fix the last high-severity gaps.
- Decide go/no-go for a beta or pilot release.

Deliverables:
- Release readiness checklist.
- Outstanding-risk list.
- Beta recommendation memo.

## Milestone Summary

- Weeks 1-2: protect platform access.
- Weeks 3-4: stabilize deployment and CI.
- Weeks 5-6: expand confidence and fix docs.
- Weeks 7-8: finish mobile MVP contract and review release readiness.

## Optional Stretch Items

- Add rate limiting to sensitive auth/search/mobile endpoints.
- Add policy coverage for more tenant modules.
- Add seeded demo tenants for QA and onboarding.
- Add observability and health dashboards.
