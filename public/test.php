<?php
// Secure with a simple query parameter so random people don't see it
if (($_GET['key'] ?? '') !== 'nagiyevdebug') {
    die('Unauthorized');
}

define('LARAVEL_START', microtime(true));

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

echo "<pre>";

// 1. Clear Route & Config cache
try {
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    
    echo "Clearing route cache...\n";
    Artisan::call('route:clear');
    echo Artisan::output() . "\n";
    
    echo "Clearing config cache...\n";
    Artisan::call('config:clear');
    echo Artisan::output() . "\n";
} catch (\Exception $e) {
    echo "Artisan error: " . $e->getMessage() . "\n";
}

// 2. Check Directory Write Status
echo "\n--- STORAGE DIRECTORIES DIAGNOSTIC ---\n";
$dirs = [
    storage_path('app'),
    storage_path('app/originals'),
    storage_path('app/public'),
    storage_path('app/public/web'),
    storage_path('app/public/thumbnails'),
    storage_path('app/zips'),
];

foreach ($dirs as $dir) {
    $exists = is_dir($dir) ? 'EXISTS' : 'MISSING';
    $writable = is_writable($dir) ? 'WRITABLE' : 'NOT WRITABLE';
    $perms = is_dir($dir) ? substr(sprintf('%o', fileperms($dir)), -4) : 'N/A';
    echo "$dir: [$exists] [$writable] (perms: $perms)\n";
    
    // If missing, try to create it
    if (!is_dir($dir)) {
        echo "   -> Attempting to create $dir...\n";
        try {
            $created = mkdir($dir, 0755, true);
            echo "   -> Created: " . ($created ? 'SUCCESS' : 'FAILED') . "\n";
        } catch (\Exception $e) {
            echo "   -> Create error: " . $e->getMessage() . "\n";
        }
    }
}

// 3. Print Latest Logs
echo "\n--- LATEST LARAVEL LOG LINES ---\n";
$logPath = storage_path('logs/laravel.log');
if (file_exists($logPath)) {
    $lines = file($logPath);
    $lastLines = array_slice($lines, -100);
    echo implode("", $lastLines);
} else {
    echo "No laravel.log file found at $logPath\n";
}

echo "</pre>";
