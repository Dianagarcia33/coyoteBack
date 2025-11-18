<?php
/**
 * Cache clearing script for production without console access
 * Upload this file to your server root and access it via browser
 * DELETE THIS FILE after running it for security
 */

// Adjust this path to your Laravel installation
$laravelPath = __DIR__;

echo "<h1>Laravel Cache Clearer</h1>";
echo "<pre>";

// Clear bootstrap cache
$bootstrapCache = $laravelPath . '/bootstrap/cache';
$files = [
    'config.php',
    'routes-v7.php',
    'packages.php',
    'services.php',
];

echo "Clearing bootstrap cache...\n";
foreach ($files as $file) {
    $path = $bootstrapCache . '/' . $file;
    if (file_exists($path)) {
        if (unlink($path)) {
            echo "✓ Deleted: $file\n";
        } else {
            echo "✗ Failed to delete: $file\n";
        }
    } else {
        echo "- Not found: $file\n";
    }
}

// Clear storage framework cache
$storagePaths = [
    $laravelPath . '/storage/framework/cache/data',
    $laravelPath . '/storage/framework/views',
    $laravelPath . '/storage/framework/sessions',
];

echo "\nClearing storage framework cache...\n";
foreach ($storagePaths as $dir) {
    if (is_dir($dir)) {
        $deleted = 0;
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        
        foreach ($files as $fileinfo) {
            if ($fileinfo->isFile() && $fileinfo->getFilename() !== '.gitignore') {
                if (unlink($fileinfo->getRealPath())) {
                    $deleted++;
                }
            }
        }
        echo "✓ Cleared " . basename($dir) . ": $deleted files\n";
    } else {
        echo "- Directory not found: " . basename($dir) . "\n";
    }
}

echo "\n<strong>Cache cleared successfully!</strong>\n";
echo "\n<span style='color: red; font-weight: bold;'>IMPORTANT: Delete this file now for security!</span>\n";
echo "</pre>";
