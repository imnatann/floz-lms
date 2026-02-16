<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

use OpenApi\Attributes as OA;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return Inertia::render('Auth/Login');
    }

    #[OA\Post(
        path: "/login",
        tags: ["Auth"],
        summary: "User Login",
        description: "Authenticate user and return redirect or token"
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ["email", "password"],
            properties: [
                new OA\Property(property: "email", type: "string", format: "email", example: "admin@floz.id"),
                new OA\Property(property: "password", type: "string", format: "password", example: "secret"),
                new OA\Property(property: "remember", type: "boolean", example: false),
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: "Login Successful",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "message", type: "string", example: "Login successful")
            ]
        )
    )]
    #[OA\Response(response: 401, description: "Invalid credentials")]
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $remember = $request->boolean('remember');

        // IdentifyTenant middleware has already:
        // - Detected the tenant from the subdomain
        // - Configured the tenant DB connection
        // - Switched Auth provider to Tenant\User
        // - Stored tenant info in session
        // If no tenant was found (platform domain), Auth uses the central User model.

        $tenant = app()->bound('currentTenant') ? app('currentTenant') : null;

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();
            $user = Auth::user();

            if ($tenant) {
                // Use Inertia::location for a full page visit that preserves the subdomain
                return \Inertia\Inertia::location(url('/tenant/dashboard'));
            }

            // Platform login — only allow admins
            if ($user->isSuperAdmin()) {
                return redirect()->route('platform.dashboard');
            }

            // Non-admin on platform domain — deny access
            Auth::logout();
            return back()->withErrors(['email' => 'Access denied. Administrator privileges required.']);
        }

        return back()->withErrors([
            'email' => 'Email atau password salah.',
        ]);
    }

    #[OA\Post(
        path: "/logout",
        tags: ["Auth"],
        summary: "User Logout",
        description: "Logout the authenticated user"
    )]
    #[OA\Response(response: 302, description: "Redirect to home")]
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
    #[OA\Get(
        path: "/api/tenants/search",
        tags: ["Auth"],
        summary: "Search Tenants",
        description: "Search for tenants by name or domain"
    )]
    #[OA\Parameter(
        name: "q",
        in: "query",
        required: true,
        schema: new OA\Schema(type: "string")
    )]
    #[OA\Response(
        response: 200,
        description: "List of tenants",
        content: new OA\JsonContent(
            type: "array",
            items: new OA\Items(
                properties: [
                    new OA\Property(property: "id", type: "string"),
                    new OA\Property(property: "name", type: "string"),
                    new OA\Property(property: "domain", type: "string"),
                    new OA\Property(property: "url", type: "string"),
                    new OA\Property(property: "logo_url", type: "string", nullable: true),
                ]
            )
        )
    )]
    public function searchTenants(Request $request)
    {
        $query = $request->input('q');

        if (strlen($query) < 3) {
            return response()->json([]);
        }

        // explicit start query
        $queryBuilder = \App\Models\Central\Tenant::query();

        // Add search conditions using whereRaw for maximum compatibility
        $term = strtolower($query);
        $queryBuilder->where(function ($q) use ($term) {
             $q->whereRaw("LOWER(name) LIKE ?", ["%{$term}%"])
               ->orWhereRaw("LOWER(slug) LIKE ?", ["%{$term}%"])
               ->orWhereRaw("LOWER(domain) LIKE ?", ["%{$term}%"]);
        });

        // We don't enforce whereNotNull('domain') anymore because we can use slug
        
        $tenants = $queryBuilder->select(['id', 'name', 'slug', 'domain', 'logo_url'])
            ->limit(10)
            ->get();
        
        $results = $tenants->map(function ($tenant) {
                 $request = request();
                 $protocol = $request->secure() ? 'https://' : 'http://';
                 $host = $request->getHost();
                 $port = $request->getPort();
                 $portSuffix = ($port && !in_array($port, [80, 443])) ? ':' . $port : '';

                 // Use domain if available, otherwise fallback to slug
                 $domainOrSlug = $tenant->domain ?? $tenant->slug;
                 
                 // Logic to determine full domain
                 $fullDomain = $domainOrSlug;

                 // If it's a slug (no dots), append central domain/localhost
                 if (!str_contains($domainOrSlug, '.')) {
                     if ($host === 'localhost') {
                         $fullDomain = $domainOrSlug . '.localhost';
                     } else {
                         $central = config('tenancy.central_domain');
                         if ($central && $central !== 'localhost') {
                              $fullDomain = $domainOrSlug . '.' . $central;
                         }
                     }
                 }

                 return [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'domain' => $domainOrSlug, // Display slug/domain
                    'url' => $protocol . $fullDomain . $portSuffix . '/login',
                    'logo_url' => $tenant->logo_url,
                 ];
            });

        return response()->json($results);
    }
}
