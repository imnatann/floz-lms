<?php

$host = 'smp01-taruna.localhost';

echo "Simulating Login Page Request (GET) for Host: {$host}\n";

$request = Illuminate\Http\Request::create('/login', 'GET');
$request->headers->set('Host', $host);

$response = app()->handle($request);

echo "Status: " . $response->getStatusCode() . "\n";
// Check if inertia props have tenant
if (str_contains($response->getContent(), 'smp01-taruna')) {
    echo "SUCCESS: Tenant slug found in response content/props.\n";
} else {
    echo "FAILURE: Tenant slug NOT found in response.\n";
    // echo substr($response->getContent(), 0, 500);
}

echo "\n----------------\n";

echo "Simulating Login POST Request for Host: {$host}\n";
$details = [
    'email' => 'guru@sekolah.id',
    'password' => 'password',
    '_token' => csrf_token(), // This won't work in isolation without session, but let's see if middleware runs
];

// Note: Testing POST with CSRF in Tinker is hard.
// We primarily want to know if IdentifyTenant ran.
// The GET request confirming "tenant" prop is enough to prove middleware is active.
