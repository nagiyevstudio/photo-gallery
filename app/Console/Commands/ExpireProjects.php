<?php

namespace App\Console\Commands;

use App\Models\Project;
use Illuminate\Console\Command;

class ExpireProjects extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'projects:expire';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically archive expired project galleries';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $expiredProjectsCount = Project::active()
            ->where('expires_at', '<', now())
            ->update(['status' => 'archived']);

        $this->info("Successfully archived {$expiredProjectsCount} expired project(s).");
        return Command::SUCCESS;
    }
}
