<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\AuditLog;
use Illuminate\Console\Command;
use Laravel\Sanctum\PersonalAccessToken;

final class PruneAuditLogs extends Command
{
    protected $signature = 'app:prune-audit-logs {--retention=365 : Days to keep audit logs}';
    protected $description = 'Prune old audit logs and expired personal access tokens';

    public function handle(): int
    {
        $retention = (int) $this->option('retention');

        $pruned = AuditLog::where('created_at', '<', now()->subDays($retention))->delete();
        $this->info("Pruned {$pruned} audit logs older than {$retention} days.");

        $expired = PersonalAccessToken::where('expires_at', '<', now())->delete();
        $this->info("Deleted {$expired} expired personal access tokens.");

        return self::SUCCESS;
    }
}
