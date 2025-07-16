<?php

declare(strict_types=1);

namespace Shavonn\StatusMachina\Commands;

use Illuminate\Console\Command;
use Shavonn\StatusMachina\History\StateTransitionRepository;

class PruneStateHistory extends Command
{
    protected $signature = 'status-machina:prune-history {--days= : Number of days to retain}';

    protected $description = 'Prune old state transition history';

    public function handle(StateTransitionRepository $repository): int
    {
        $days = $this->option('days') ? (int) $this->option('days') : null;

        $this->info('Pruning state transition history...');

        $deleted = $repository->prune($days);

        $this->info("Pruned {$deleted} old state transitions.");

        return Command::SUCCESS;
    }
}
