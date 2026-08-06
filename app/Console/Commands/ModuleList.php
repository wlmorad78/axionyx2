<?php

namespace App\Console\Commands;

use App\Services\ModuleRegistry;
use App\Services\ModuleInstaller;
use App\Models\Module;
use Illuminate\Console\Command;

class ModuleList extends Command
{
    protected $signature = 'module:list';
    protected $description = 'List all registered modules';

    public function handle()
    {
        $modules = ModuleRegistry::all();

        if (empty($modules)) {
            $this->warn('No modules found in Modules/ directory.');
            return 0;
        }

        $rows = [];
        foreach ($modules as $code => $m) {
            $status = $m['is_enabled'] ? '<info>enabled</info>' : ($m['status'] === 'installed' ? '<comment>disabled</comment>' : '<fg=red>pending</fg=red>');
            $deps = implode(', ', $m['dependencies'] ?? []);
            $rows[] = [$code, $m['name'], $m['version'], $status, $deps ?: '-'];
        }

        $this->table(['Code', 'Name', 'Version', 'Status', 'Dependencies'], $rows);
        return 0;
    }
}
