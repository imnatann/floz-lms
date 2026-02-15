<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\Concerns\InteractsWithSession;
use Illuminate\Foundation\Testing\Concerns\InteractsWithContainer;
use Tests\TestCase; // Assuming you have a base test class, or we mock the request manually

// Since we are in Tinker/Script, we can't easily use TestCase methods directly without setup.
// We will manually construct the request and dispatch it.

$host = 'smp01-taruna.localhost';
$details = [
    'email' => 'guru@sekolah.id',
    'password' => 'password',
];

echo "Simulating Login Request for Host: {$host}\n";

// Mock the request
$request = Illuminate\Http\Request::create('/login', 'POST', $details);
$request->headers->set('Host', $host);

// Dispatch the request
try {
    $response = app()->handle($request);
    
    echo "Status Code: " . $response->getStatusCode() . "\n";
    
    if ($response->isRedirect()) {
        echo "Redirect Target: " . $response->getTargetUrl() . "\n";
        
        // Start session to check data
        if ($response->getSession()) {
             $session = $response->getSession();
             $errors = $session->get('errors');
             if ($errors) {
                 echo "Errors: " . json_encode($errors->getBag('default')->messages()) . "\n";
             } else {
                 echo "Session Tenant Slug: " . $session->get('tenant_slug') . "\n";
                 echo "Login Successful!\n";
             }
        }
    } else {
        echo "Response Content: " . substr($response->getContent(), 0, 500) . "...\n";
    }

} catch (\Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
