<?php

namespace App\Console\Commands;

use App\Services\ModuleInstaller;
use Illuminate\Console\Command;

class ModuleUninstall extends Command
{
    protected $signature = 'module:uninstall {code}';
    protected $description = 'Uninstall a module';

    public function handle()
    {
        $code = $this->argument('code');

        if (!$this->confirm("Are you sure you want to uninstall module '{$code}'?")) {
            return 0;
        }

        $result = ModuleInstaller::uninstall($code);

        if ($result['success']) {
            $this->info("✓ {$result['message']}");
        } else {
            $this->error("✗ {$result['message']}");
            return 1;
        }

        return 0;
    }
}
