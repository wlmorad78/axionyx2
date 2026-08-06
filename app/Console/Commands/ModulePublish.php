<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ModulePublish extends Command
{
    protected $signature = 'module:publish {module} {--path=}';
    protected $description = 'Publish a module from external source to Modules directory';

    public function handle(): int
    {
        $module = $this->argument('module');
        $path = $this->option('path');

        if (!$path) {
            $this->error('Please provide --path=<source_path>');
            return 1;
        }

        if (!is_dir($path)) {
            $this->error("Source path does not exist: {$path}");
            return 1;
        }

        $moduleJson = "{$path}/module.json";
        if (!file_exists($moduleJson)) {
            $this->error("Not a valid module: module.json not found");
            return 1;
        }

        $manifest = json_decode(file_get_contents($moduleJson), true);
        $slug = $manifest['code'] ?? $module;
        $studly = \Str::studly($slug);

        $dest = base_path("Modules/{$studly}");

        if (is_dir($dest)) {
            $this->warn("Module '{$studly}' already exists. Overwriting...");
        }

        // Copy directory
        $this->copyDirectory($path, $dest);

        $this->info("Module '{$studly}' published to Modules/{$studly}");
        $this->line("Run: php artisan module:install {$slug}");

        return 0;
    }

    protected function copyDirectory(string $src, string $dest): void
    {
        if (!is_dir($dest)) mkdir($dest, 0755, true);

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($src, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($items as $item) {
            $target = $dest . DIRECTORY_SEPARATOR . $item->getRelativePathName();
            if ($item->isDir()) {
                if (!is_dir($target)) mkdir($target, 0755, true);
            } else {
                copy($item->getPathname(), $target);
            }
        }
    }
}
