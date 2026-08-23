<?php

namespace App\Services;

use App\Models\AppVersion;
use Illuminate\Support\Facades\Log;

class AppVersionService
{
    public function getLatestVersion(string $platform = 'android'): ?AppVersion
    {
        return AppVersion::active()
            ->forPlatform($platform)
            ->latestBuild()
            ->first();
    }

    public function createVersion(array $data): AppVersion
    {
        $this->validateBuild($data);

        $version = AppVersion::create($data);

        Log::info("App version created: {$version->version}+{$version->build} for {$version->platform}");

        return $version;
    }

    public function validateBuild(array $data): void
    {
        $platform = $data['platform'] ?? 'android';
        $newBuild = $data['build'] ?? 0;

        $latestBuild = AppVersion::where('platform', $platform)
            ->orderByDesc('build')
            ->value('build');

        if ($latestBuild !== null && $newBuild <= $latestBuild) {
            throw new \InvalidArgumentException(
                "Build number {$newBuild} must be greater than the current latest build {$latestBuild}"
            );
        }

        $existingVersion = AppVersion::where('version', $data['version'] ?? '')
            ->where('platform', $platform)
            ->exists();

        if ($existingVersion) {
            throw new \InvalidArgumentException(
                "Version {$data['version']} already exists for platform {$platform}"
            );
        }
    }

    public function deactivateOldVersions(string $platform, int $keepBuild): void
    {
        AppVersion::where('platform', $platform)
            ->where('build', '<', $keepBuild)
            ->update(['is_active' => false]);
    }
}
