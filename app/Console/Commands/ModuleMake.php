<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ModuleMake extends Command
{
    protected $signature = 'module:make {name} {--author=AXIONYX} {--description=} {--description_ar=} {--dependencies=}';
    protected $description = 'Scaffold a new module with all required files';

    public function handle(): int
    {
        $name = $this->argument('name');
        $slug = \Str::slug($name, '_');
        $studly = \Str::studly($name);
        $author = $this->option('author');
        $description = $this->option('description') ?: "{$studly} module";
        $descriptionAr = $this->option('description_ar') ?: $slug;
        $dependencies = $this->option('dependencies') ? explode(',', $this->option('dependencies')) : [];

        $modulePath = base_path("Modules/{$studly}");

        if (is_dir($modulePath)) {
            $this->error("Module '{$studly}' already exists!");
            return 1;
        }

        // Create directories
        $dirs = [
            '', 'Config', 'Permissions', 'Menu', 'Lang/en', 'Lang/ar',
            'Migrations', 'Routes', 'Resources',
        ];
        foreach ($dirs as $dir) {
            $path = $dir ? "{$modulePath}/{$dir}" : $modulePath;
            if (!is_dir($path)) mkdir($path, 0755, true);
        }

        // module.json
        $this->createFile("{$modulePath}/module.json", json_encode([
            'code' => $slug,
            'name' => $studly,
            'name_ar' => $descriptionAr,
            'version' => '1.0.0',
            'description' => $description,
            'description_ar' => $descriptionAr,
            'author' => $author,
            'is_core' => false,
            'dependencies' => $dependencies,
            'capabilities' => ["{$slug}_access"],
            'config' => [],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // Config/features.php
        $this->createFile("{$modulePath}/Config/features.php", "<?php\nreturn [\n    '{$slug}' => [\n        'name' => '{$studly}',\n        'name_ar' => '{$descriptionAr}',\n        'category' => 'custom',\n        'sort_order' => 50,\n    ],\n];\n");

        // Permissions/permissions.php
        $perms = [
            "{$slug}.view",
            "{$slug}.create",
            "{$slug}.edit",
            "{$slug}.delete",
            "{$slug}.export",
        ];
        $this->createFile("{$modulePath}/Permissions/permissions.php", "<?php\nreturn [\n" . implode("\n", array_map(fn($p) => "    '{$p}',", $perms)) . "\n];\n");

        // Menu/menu.php
        $this->createFile("{$modulePath}/Menu/menu.php", "<?php\nreturn [\n    'items' => [\n        [\n            'key' => '{$slug}',\n            'feature' => '{$slug}',\n            'permission' => '{$slug}.view',\n            'title_en' => '{$studly}',\n            'title_ar' => '{$descriptionAr}',\n            'icon' => 'extension',\n            'color' => '#6366F1',\n            'order' => 50,\n        ],\n    ],\n];\n");

        // Lang/en/messages.php
        $this->createFile("{$modulePath}/Lang/en/messages.php", "<?php\nreturn [\n    'welcome' => 'Welcome to {$studly}',\n];\n");

        // Lang/ar/messages.php
        $this->createFile("{$modulePath}/Lang/ar/messages.php", "<?php\nreturn [\n    'welcome' => 'مرحباً بك في {$descriptionAr}',\n];\n");

        // Migrations/001_create_{$slug}_table.php
        $this->createFile("{$modulePath}/Migrations/001_create_{$slug}_table.php", "<?php\n\nuse Illuminate\Database\Migrations\Migration;\nuse Illuminate\Database\Schema\Blueprint;\nuse Illuminate\Support\Facades\Schema;\n\nreturn new class extends Migration\n{\n    public function up(): void\n    {\n        Schema::create('{$slug}', function (Blueprint \$table) {\n            \$table->id();\n            \$table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();\n            \$table->string('name');\n            \$table->text('description')->nullable();\n            \$table->boolean('is_active')->default(true);\n            \$table->timestamps();\n            \$table->softDeletes();\n        });\n    }\n\n    public function down(): void\n    {\n        Schema::dropIfExists('{$slug}');\n    }\n};\n");

        // Routes/api.php
        $this->createFile("{$modulePath}/Routes/api.php", "<?php\n\nuse Illuminate\Support\Facades\Route;\n\nRoute::get('{$slug}', function () {\n    return response()->json(['message' => '{$studly} endpoint']);\n});\n");

        // README.md
        $this->createFile("{$modulePath}/README.md", "# {$studly}\n\n{$description}\n\n## Permissions\n" . implode("\n", array_map(fn($p) => "- `{$p}`", $perms)) . "\n\n## Installation\n```bash\nphp artisan module:install {$slug}\n```\n");

        $this->info("Module '{$studly}' created successfully!");
        $this->newLine();
        $this->info("Files created:");
        $this->line("  {$modulePath}/module.json");
        $this->line("  {$modulePath}/Config/features.php");
        $this->line("  {$modulePath}/Permissions/permissions.php");
        $this->line("  {$modulePath}/Menu/menu.php");
        $this->line("  {$modulePath}/Lang/en/messages.php");
        $this->line("  {$modulePath}/Lang/ar/messages.php");
        $this->line("  {$modulePath}/Migrations/001_create_{$slug}_table.php");
        $this->line("  {$modulePath}/Routes/api.php");
        $this->line("  {$modulePath}/README.md");
        $this->newLine();
        $this->info("Next steps:");
        $this->line("  1. Edit {$modulePath}/Migrations/ to add your columns");
        $this->line("  2. Edit {$modulePath}/Routes/api.php to add your endpoints");
        $this->line("  3. Run: php artisan module:install {$slug}");

        return 0;
    }

    protected function createFile(string $path, string $content): void
    {
        file_put_contents($path, $content);
    }
}
