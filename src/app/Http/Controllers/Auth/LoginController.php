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
}
