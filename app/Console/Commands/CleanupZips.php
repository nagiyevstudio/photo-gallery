<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class CleanupZips extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'zips:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up generated ZIP archives older than 24 hours';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $zipDirectory = storage_path('app/zips');

        if (!File::isDirectory($zipDirectory)) {
            $this->info('ZIP directory does not exist. Nothing to clean.');
            return Command::SUCCESS;
        }

        $files = File::files($zipDirectory);
        $deletedCount = 0;

        foreach ($files as $file) {
            // Check if file is older than 24 hours
            if (time() - File::lastModified($file) > 86400) {
                File::delete($file);
                $deletedCount++;
            }
        }

        $this->info("Successfully cleaned up {$deletedCount} old ZIP file(s).");
        return Command::SUCCESS;
    }
}
