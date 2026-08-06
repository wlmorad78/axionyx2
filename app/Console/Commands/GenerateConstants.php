<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateConstants extends Command
{
    protected $signature = 'axionyx:generate-constants';
    protected $description = 'Generate PHP constants classes from permissions, features, and modules';

    public function handle()
    {
        $this->generatePermissionsConstants();
        $this->generateFeaturesConstants();
        $this->generateModulesConstants();

        $this->newLine();
        $this->info('✓ Constants generated successfully!');
        $this->info('  → app/Constants/PermissionConstants.php');
        $this->info('  → app/Constants/FeatureConstants.php');
        $this->info('  → app/Constants/ModuleConstants.php');

        return 0;
    }

    protected function generatePermissionsConstants(): void
    {
        $permissions = \App\Models\Permission::orderBy('code')->get();
        $grouped = [];
        foreach ($permissions as $p) {
            $parts = explode('.', $p->code);
            $group = $parts[0] ?? 'general';
            $grouped[$group][] = $p->code;
        }

        $content = "<?php\n\nnamespace App\Constants;\n\n";
        $content .= "class PermissionConstants\n{\n";

        foreach ($grouped as $group => $perms) {
            $content .= "    // ─── " . ucfirst($group) . " ───\n";
            foreach ($perms as $perm) {
                $constName = $this->toCamelCase($perm);
                $content .= "    public const " . strtoupper($constName) . " = '{$perm}';\n";
            }
            $content .= "\n";
        }

        // Wildcard constants
        $content .= "    // ─── Wildcards ───\n";
        $content .= "    public const ALL = '*';\n";

        $modules = $permissions->pluck('code')->map(fn($c) => explode('.', $c)[0])->unique()->toArray();
        foreach ($modules as $mod) {
            $constName = strtoupper($mod) . '_ALL';
            $content .= "    public const {$constName} = '{$mod}.*';\n";
        }

        $content .= "}\n";

        $dir = app_path('Constants');
        if (!File::isDirectory($dir)) File::makeDirectory($dir, 0755, true);
        File::put($dir . '/PermissionConstants.php', $content);
    }

    protected function generateFeaturesConstants(): void
    {
        $features = \App\Models\Feature::orderBy('code')->get();

        $content = "<?php\n\nnamespace App\Constants;\n\n";
        $content .= "class FeatureConstants\n{\n";

        foreach ($features as $f) {
            $constName = $this->toCamelCase($f->code);
            $content .= "    public const " . strtoupper($constName) . " = '{$f->code}';\n";
        }

        $content .= "}\n";

        File::put(app_path('Constants') . '/FeatureConstants.php', $content);
    }

    protected function generateModulesConstants(): void
    {
        $modules = \App\Models\Module::orderBy('code')->get();

        $content = "<?php\n\nnamespace App\Constants;\n\n";
        $content .= "class ModuleConstants\n{\n";

        foreach ($modules as $m) {
            $constName = $this->toCamelCase($m->code);
            $content .= "    public const " . strtoupper($constName) . " = '{$m->code}';\n";
        }

        $content .= "}\n";

        File::put(app_path('Constants') . '/ModuleConstants.php', $content);
    }

    protected function toCamelCase(string $input): string
    {
        $parts = explode('.', $input);
        $result = '';
        foreach ($parts as $i => $part) {
            $result .= $i === 0 ? lcfirst(ucfirst($part)) : ucfirst($part);
        }
        return $result;
    }
}
