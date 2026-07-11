<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;

final class TokensPurge extends Command
{
    protected $signature = 'tokens:purge';
    protected $description = 'Alias: prune expired audit logs and tokens';

    public function handle(): int
    {
        return $this->call(PruneAuditLogs::class);
    }
}
