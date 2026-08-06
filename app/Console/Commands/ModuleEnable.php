<?php

namespace App\Console\Commands;

use App\Services\ModuleInstaller;
use Illuminate\Console\Command;

class ModuleEnable extends Command
{
    protected $signature = 'module:enable {code}';
    protected $description = 'Enable a module';

    public function handle()
    {
        $result = ModuleInstaller::enable($this->argument('code'));
        $result['success'] ? $this->info("✓ {$result['message']}") : $this->error("✗ {$result['message']}");
        return $result['success'] ? 0 : 1;
    }
}
