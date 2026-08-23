<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppVersionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'version' => $this->version,
            'build' => $this->build,
            'download_url' => $this->buildDownloadUrl($request),
            'force_update' => $this->force_update,
            'release_notes' => $this->release_notes ?? [],
            'release_date' => $this->release_date?->format('Y-m-d'),
            'minimum_supported_version' => $this->minimum_supported_version,
            'minimum_supported_build' => $this->minimum_supported_build,
            'file_size' => $this->file_size,
            'checksum' => $this->checksum,
            'platform' => $this->platform,
        ];
    }

    private function buildDownloadUrl(Request $request): ?string
    {
        if (!$this->download_url) {
            return null;
        }

        if (str_starts_with($this->download_url, 'http://') || str_starts_with($this->download_url, 'https://')) {
            return $this->download_url;
        }

        $baseUrl = $request->getSchemeAndHttpHost();
        $path = ltrim($this->download_url, '/');

        return $baseUrl . '/' . $path;
    }
}
