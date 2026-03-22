# Platform and Tenant Auth Boundary Audit

## Conclusion

There is a real boundary risk in the current web routing setup. The login flow tries to steer central super admins to platform pages and tenant users to tenant pages, but platform route protection itself is still too permissive. Right now the main `/platform` route group appears to trust generic `auth` middleware without an explicit platform-only domain and role gate.

This means the system relies too much on happy-path login behavior instead of enforcing the boundary at the route layer.

## What I Checked

- `src/routes/web.php`
- `src/bootstrap/app.php`
- `src/config/auth.php`
- `src/app/Http/Middleware/IdentifyTenant.php`
- `src/app/Http/Middleware/ResolveTenantFromSession.php`
- `src/app/Http/Middleware/TenantAccess.php`
- `src/app/Http/Controllers/Auth/LoginController.php`
- `src/app/Http/Controllers/Platform/DashboardController.php`
- `src/app/Http/Controllers/Platform/TenantController.php`
- `src/app/Models/User.php`
- `src/app/Models/Tenant/User.php`

## Findings

### 1. Platform routes use only generic auth

In `src/routes/web.php:39`, the `/platform` group is protected by `->middleware(['auth'])`.

Risk:
- `auth` only checks whether a user is authenticated for the active provider/guard context.
- It does not, by itself, prove the request is on the correct host.
- It does not, by itself, prove the user is a central super admin.

Impact:
- If a tenant-authenticated session can remain valid while requesting `/platform/*`, the platform controllers may execute with the wrong trust assumptions.

### 2. Tenant identification changes the auth model dynamically

In `src/app/Http/Middleware/IdentifyTenant.php:44-50`, tenant requests dynamically switch the auth provider model to `App\Models\Tenant\User`.

This is correct for tenant requests, but it also means route protection must be strict about context. Otherwise:
- tenant-host requests use tenant auth models,
- platform-host requests use central auth models,
- but `/platform/*` itself does not explicitly assert that only the central model and central host are allowed.

### 3. Login flow is stricter than route protection

In `src/app/Http/Controllers/Auth/LoginController.php:68-80`:
- tenant-context login redirects to `/tenant/dashboard`,
- platform-context login only allows users where `isSuperAdmin()` is true.

That is good as an entry rule, but it is not enough as a boundary rule.

Reason:
- A secure system cannot rely only on login redirect logic.
- Every protected route still needs its own correct authorization boundary.

### 4. Platform controllers do not add their own guardrail

`src/app/Http/Controllers/Platform/DashboardController.php` and `src/app/Http/Controllers/Platform/TenantController.php` query central data directly and assume platform context.

I did not find route-level or controller-level checks here for:
- central host enforcement,
- central user model enforcement,
- super-admin role enforcement.

Impact:
- Even if the UI hides links, direct requests remain a concern.

### 5. Existing tenant-specific middleware exists but is not wired into route protection

There are middleware classes in:
- `src/app/Http/Middleware/TenantAccess.php`
- `src/app/Http/Middleware/ResolveTenantFromSession.php`

But from the current routing/bootstrap usage, they do not appear to be the main access-control layer for tenant versus platform route groups.

This suggests the current design has some intended guardrails that are not fully integrated.

### 6. Shared auth props already acknowledge mixed model contexts

In `src/app/Http/Middleware/HandleInertiaRequests.php:45-50`, the code explicitly avoids tenant policy checks when the user is not an instance of `App\Models\Tenant\User`.

That is another strong signal that mixed central and tenant auth contexts are real and need stronger route-layer enforcement.

## Likely Attack or Failure Modes

### Scenario A - Tenant user hits platform URL directly

If a tenant user is authenticated and requests `/platform/dashboard` or `/platform/tenants` from a context where auth still passes, the current route definition does not clearly block them before controller execution.

### Scenario B - Domain/context confusion in local development

Because `IdentifyTenant` uses host parsing and local behavior for `localhost` subdomains, local sessions can be especially prone to context confusion if hostnames and sessions are reused during testing.

### Scenario C - Future regression after auth refactors

Even if current behavior happens to be safe in common flows, the absence of explicit platform middleware means a later auth or session change could silently open access.

## Recommended Fix

### Add explicit platform middleware

Create middleware that enforces all of the following before entering `/platform/*`:
- no tenant is currently resolved,
- request host matches the configured central/platform domain rules,
- authenticated user is an instance of the central user model,
- authenticated user has `super_admin` role.

### Apply it at the route group

Update the platform route group in `src/routes/web.php` so it requires more than `auth`, for example a combination like:
- `auth`
- `platform.access`

### Consider explicit tenant route middleware too

For `/tenant/*`, add or wire middleware that asserts:
- a tenant is resolved,
- authenticated user is a tenant user,
- optional subscription checks where needed.

## Minimum Test Cases to Add

1. Super admin on central domain can access `/platform/dashboard`.
2. School admin on tenant domain cannot access `/platform/dashboard`.
3. Teacher on tenant domain cannot access `/platform/tenants`.
4. Student on tenant domain cannot access any `/platform/*` route.
5. Unauthenticated user is redirected from `/platform/*`.
6. Central user without `super_admin` role is denied from `/platform/*`.
7. Tenant user can access `/tenant/dashboard` only when tenant context is resolved.

## Severity

`High`

The issue is high severity because it affects the trust boundary between the SaaS owner surface and school-level tenant users. Even if there is no confirmed exploit yet, the route protection model is currently weaker than the architecture requires.

## Recommended Next Step

Implement platform-only middleware first, then add regression tests before moving on to broader production-readiness work.
