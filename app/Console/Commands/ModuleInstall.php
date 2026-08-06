<?php

namespace App\Console\Commands;

use App\Services\ModuleInstaller;
use Illuminate\Console\Command;

class ModuleInstall extends Command
{
    protected $signature = 'module:install {code}';
    protected $description = 'Install a module';

    public function handle()
    {
        $code = $this->argument('code');

        $this->info("Installing module '{$code}'...");

        $result = ModuleInstaller::install($code);

        if ($result['success']) {
            $this->info("✓ {$result['message']}");
        } else {
            $this->error("✗ {$result['message']}");
            if (isset($result['errors'])) {
                foreach ($result['errors'] as $err) {
                    $this->error("  - {$err}");
                }
            }
            return 1;
        }

        return 0;
    }
}
