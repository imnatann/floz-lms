<?php

$slug = 'smp01-taruna';
$email = 'guru@sekolah.id';
$password = 'password';

$tenant = \App\Models\Central\Tenant::where('slug', $slug)->first();
if (!$tenant) { die("Tenant not found\n"); }

echo "Found Tenant: {$tenant->name}\n";

// Configure DB
\Illuminate\Support\Facades\Config::set('database.connections.tenant.database', $tenant->database_name);
\Illuminate\Support\Facades\DB::purge('tenant');
\Illuminate\Support\Facades\DB::reconnect('tenant');

echo "Connected to Tenant DB: {$tenant->database_name}\n";

// Check User directly
$user = \App\Models\Tenant\User::where('email', $email)->first();

if ($user) {
    echo "User Found: {$user->name} (ID: {$user->id})\n";
    if (\Illuminate\Support\Facades\Hash::check($password, $user->password)) {
        echo "PASSWORD MATCH! Login logic is valid.\n";
    } else {
        echo "Password mismatch.\n";
    }
} else {
    echo "User NOT found in tenant database.\n";
}
