<?php
$tenants = App\Models\Central\Tenant::all(['slug', 'domain', 'database_name', 'name']);
foreach ($tenants as $tenant) {
    echo "Name: " . $tenant->name . "\n";
    echo "Slug: " . $tenant->slug . "\n";
    echo "Domain: " . $tenant->domain . "\n";
    echo "Database: " . $tenant->database_name . "\n";
    echo "--------------------------\n";
}
