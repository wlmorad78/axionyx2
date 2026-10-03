<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use RuntimeException;

/**
 * قراءة صور بطاقات الهوية إلى نص خام عبر Tesseract.
 * التدقيق واستخراج الحقول يتم في IdCardParser.
 */
class IdCardOcrService
{
    /**
     * تحويل صورة البطاقة إلى نص.
     *
     * @throws RuntimeException عند غياب Tesseract أو فشل القراءة
     */
    public function recognize(UploadedFile $file): string
    {
        if (!function_exists('exec')) {
            throw new RuntimeException('دالة exec معطّلة على الخادم، لا يمكن تشغيل محرك القراءة');
        }

        $binary = $this->resolveBinary();
        if ($binary === null) {
            throw new RuntimeException('محرك قراءة Tesseract غير مثبت على الخادم');
        }

        $original = $file->getRealPath();
        if ($original === null || !is_file($original)) {
            throw new RuntimeException('تعذّر الوصول إلى صورة البطاقة');
        }

        $input = $this->preprocess($original);

        $langs = (string) config('ocr.languages', 'ara+eng');
        $psm = (int) config('ocr.psm', 4);

        $cmd = escapeshellarg($binary)
            . ' ' . escapeshellarg($input)
            . ' stdout -l ' . escapeshellarg($langs)
            . ' --psm ' . $psm
            . ' 2>&1';

        $output = [];
        $exitCode = 0;
        try {
            exec($cmd, $output, $exitCode);
        } finally {
            if ($input !== $original && is_file($input)) {
                @unlink($input);
            }
        }

        $text = trim(implode("\n", $output));

        if ($exitCode !== 0 && $text === '') {
            throw new RuntimeException('تعذّرت قراءة صورة البطاقة، حاول بصورة أوضح');
        }

        return $text;
    }

    /**
     * البحث عن مسار تنفيذ Tesseract (المسار الصريح أو PATH).
     */
    private function resolveBinary(): ?string
    {
        $configured = (string) config('ocr.binary', 'tesseract');

        if ($configured === '') {
            return null;
        }

        if (str_contains($configured, '/') || str_contains($configured, '\\')) {
            return is_file($configured) && is_executable($configured) ? $configured : null;
        }

        $command = strtoupper(PHP_OS_FAMILY) === 'Windows'
            ? 'where ' . escapeshellarg($configured)
            : 'command -v ' . escapeshellarg($configured);

        $output = [];
        $code = 0;
        @exec($command . ' 2>/dev/null', $output, $code);

        $path = trim($output[0] ?? '');
        return ($code === 0 && $path !== '') ? $path : null;
    }

    /**
     * تجهيز الصورة للقراءة: تدوير حسب EXIF + تدرج رمادي + تصغير.
     * يعيد مسار الملف المؤقت الجديد، أو المسار الأصلي إن تعذّر التجهيز.
     */
    private function preprocess(string $path): string
    {
        if (!extension_loaded('gd')) {
            return $path;
        }

        $binary = @file_get_contents($path);
        if ($binary === false) {
            return $path;
        }

        $image = @imagecreatefromstring($binary);
        if ($image === false) {
            return $path;
        }

        $image = $this->applyExifOrientation($image, $path);

        $max = max(400, (int) config('ocr.max_dimension', 2400));
        $width = imagesx($image);
        $height = imagesy($image);

        if (max($width, $height) > $max) {
            $scale = $max / max($width, $height);
            $newWidth = max(1, (int) round($width * $scale));
            $newHeight = max(1, (int) round($height * $scale));
            $resized = imagecreatetruecolor($newWidth, $newHeight);
            imagecopyresampled(
                $resized,
                $image,
                0,
                0,
                0,
                0,
                $newWidth,
                $newHeight,
                $width,
                $height
            );
            imagedestroy($image);
            $image = $resized;
        }

        imagefilter($image, IMG_FILTER_GRAYSCALE);

        $temp = tempnam(sys_get_temp_dir(), 'idcard_ocr_');
        if ($temp === false) {
            imagedestroy($image);
            return $path;
        }

        $pngPath = $temp . '.png';
        @unlink($temp);
        imagepng($image, $pngPath);
        imagedestroy($image);

        return is_file($pngPath) ? $pngPath : $path;
    }

    /**
     * تدوير الصورة حسب قيمة Orientation المخزّنة في EXIF (صور الموبايل).
     *
     * @param resource $image
     * @return resource
     */
    private function applyExifOrientation($image, string $path)
    {
        if (!function_exists('exif_read_data')) {
            return $image;
        }

        $exif = @exif_read_data($path);
        $orientation = (int) ($exif['Orientation'] ?? 1);

        $angle = match ($orientation) {
            3 => 180,
            6 => 270,
            8 => 90,
            default => 0,
        };

        if ($angle === 0) {
            return $image;
        }

        $rotated = imagerotate($image, $angle, 0);
        if ($rotated === false) {
            return $image;
        }

        imagedestroy($image);
        return $rotated;
    }
}
