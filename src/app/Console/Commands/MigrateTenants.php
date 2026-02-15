<?php

namespace App\Console\Commands;

use App\Models\Central\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MigrateTenants extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenants:migrate {--refresh : Refresh the database} {--seed : Seed the database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate all tenant databases';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            $this->info("Migrating tenant: {$tenant->name} ({$tenant->database_name})");

            try {
                // Configure the tenant database connection dynamically
                Config::set('database.connections.tenant.database', $tenant->database_name);
                DB::purge('tenant');
                DB::reconnect('tenant');

                // Check if database exists (optional, depending on setup)
                // Assuming database exists since tenant exists

                $options = [
                    '--database' => 'tenant',
                    '--path' => 'database/migrations/tenant',
                    '--force' => true,
                ];

                if ($this->option('refresh')) {
                    $this->call('migrate:refresh', $options);
                } else {
                    $this->call('migrate', $options);
                }

                if ($this->option('seed')) {
                    $this->call('db:seed', [
                        '--class' => 'Database\Seeders\TenantDatabaseSeeder',
                        '--database' => 'tenant',
                        '--force' => true,
                    ]);
                }

                $this->info("Migrated tenant: {$tenant->name}");
            } catch (\Exception $e) {
                $this->error("Failed to migrate tenant {$tenant->name}: {$e->getMessage()}");
            }
        }

        $this->info('All tenants migrated.');
    }
}
