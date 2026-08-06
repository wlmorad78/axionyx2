<?php

namespace App\Console\Commands;

use App\Services\ModuleInstaller;
use Illuminate\Console\Command;

class ModuleDisable extends Command
{
    protected $signature = 'module:disable {code}';
    protected $description = 'Disable a module';

    public function handle()
    {
        $result = ModuleInstaller::disable($this->argument('code'));
        $result['success'] ? $this->info("✓ {$result['message']}") : $this->error("✗ {$result['message']}");
        return $result['success'] ? 0 : 1;
    }
}
