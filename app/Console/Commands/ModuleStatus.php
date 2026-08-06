<?php

namespace App\Console\Commands;

use App\Services\ModuleRegistry;
use App\Models\Module;
use Illuminate\Console\Command;

class ModuleStatus extends Command
{
    protected $signature = 'module:status {code}';
    protected $description = 'Show detailed status of a module';

    public function handle()
    {
        $code = $this->argument('code');
        $manifest = ModuleRegistry::get($code);
        $db = Module::where('code', $code)->first();

        if (!$manifest && !$db) {
            $this->error("Module '{$code}' not found.");
            return 1;
        }

        $this->info("Module: " . ($manifest['name'] ?? $code));
        $this->newLine();

        $rows = [
            ['Code', $code],
            ['Version', $manifest['version'] ?? $db?->version ?? 'N/A'],
            ['Status', $db?->status ?? 'not installed'],
            ['Enabled', $db?->is_enabled ? 'Yes' : 'No'],
            ['Core', ($manifest['is_core'] ?? false) ? 'Yes' : 'No'],
            ['Dependencies', implode(', ', $manifest['dependencies'] ?? []) ?: 'None'],
            ['Capabilities', implode(', ', $manifest['capabilities'] ?? []) ?: 'None'],
            ['Installed At', $db?->installed_at?->format('Y-m-d H:i') ?? 'N/A'],
            ['Path', $manifest['path'] ?? 'N/A'],
        ];

        $this->table(['Property', 'Value'], $rows);

        // Dependency check
        $errors = ModuleRegistry::validateDependencies($code);
        if (!empty($errors)) {
            $this->warn("\nDependency Issues:");
            foreach ($errors as $err) {
                $this->error("  ⚠ {$err}");
            }
        } else {
            $this->info("\n✓ All dependencies met.");
        }

        return 0;
    }
}
