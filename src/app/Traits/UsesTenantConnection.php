<?php

namespace App\Traits;

trait UsesTenantConnection
{
    /**
     * Get the database connection for the model.
     */
    public function getConnectionName(): string
    {
        return 'tenant';
    }
}
