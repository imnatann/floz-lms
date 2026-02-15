<?php
$email = 'admin@taruna.sch.id';
$tenants = \App\Models\Central\Tenant::all();

echo "Checking " . $tenants->count() . " tenants for {$email}...\n";

foreach ($tenants as $tenant) {
    echo "Tenant: {$tenant->name} ({$tenant->database_name})\n";
    
    try {
        config(['database.connections.tenant.database' => $tenant->database_name]);
        DB::purge('tenant');
        DB::reconnect('tenant');
        
        $user = DB::connection('tenant')->table('users')->where('email', $email)->first();
        
        if ($user) {
            echo "  [FOUND] ID: {$user->id}, Name: {$user->name}, Role: {$user->role}\n";
            echo "  Password Hash: " . substr($user->password, 0, 10) . "...\n";
            
            // Test password 'password'
            if (Hash::check('password', $user->password)) {
                echo "  [SUCCESS] Password 'password' matches!\n";
            } else {
                echo "  [FAIL] Password 'password' does NOT match.\n";
            }
        } else {
            echo "  [NOT FOUND]\n";
        }
    } catch (\Exception $e) {
        echo "  [ERROR] " . $e->getMessage() . "\n";
    }
    echo "---------------------------------------------------\n";
}
