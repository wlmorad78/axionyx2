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
        $preferredPsm = (int) config('ocr.psm', 4);
        $psms = array_values(array_unique([$preferredPsm, 6, 11]));

        $bestText = '';
        $bestScore = -1;
        $lastExit = 0;

        try {
            foreach ($psms as $psm) {
                [$text, $exitCode] = $this->runTesseract($binary, $input, $langs, $psm);
                $lastExit = $exitCode;

                $score = $this->score($text);
                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestText = $text;
                }

                // نتيجة قوية (رقم قومي كامل أو مؤشرات حقول كثيرة) لا تحتاج محاولة أخرى.
                if ($score >= 70) {
                    break;
                }
            }
        } finally {
            if ($input !== $original && is_file($input)) {
                @unlink($input);
            }
        }

        if ($lastExit !== 0 && trim($bestText) === '') {
            throw new RuntimeException('تعذّرت قراءة صورة البطاقة، حاول بصورة أوضح');
        }

        return trim($bestText);
    }

    /**
     * تنفيذ أمر Tesseract واحداً وإرجاع [النص, رمز الخروج].
     *
     * @return array{0: string, 1: int}
     */
    private function runTesseract(string $binary, string $input, string $langs, int $psm): array
    {
        $cmd = escapeshellarg($binary)
            . ' ' . escapeshellarg($input)
            . ' stdout -l ' . escapeshellarg($langs)
            . ' --psm ' . $psm
            . ' 2>&1';

        $output = [];
        $exitCode = 0;
        exec($cmd, $output, $exitCode);

        return [implode("\n", $output), $exitCode];
    }

    /**
     * تقييم جودة النص الخام: رقم قومي كامل أقوى إشارة، ثم مؤشرات الحقول
     * والكلمات العربية وأسطر النص.
     */
    private function score(string $text): int
    {
        if (trim($text) === '') {
            return 0;
        }

        $score = 0;
        $score += 40 * preg_match_all('/\d{14}/', $text);
        $score += 15 * preg_match_all('/\d{8,}/', $text);

        foreach (['الاسم', 'العنوان', 'ميلاد', 'الرقم القومي', 'مصر'] as $marker) {
            if (str_contains($text, $marker)) {
                $score += 12;
            }
        }

        preg_match_all('/[\x{0600}-\x{06FF}]{2,}/u', $text, $words);
        $score += 4 * count($words[0]);

        $score += min(10, substr_count($text, "\n"));

        return $score;
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
        $longest = max($width, $height);

        // تصغير الصور الكبيرة، وتكبير الصور الصغيرة لضمان وضوح النص للقراءة.
        $target = null;
        if ($longest > $max) {
            $target = $max;
        } elseif ($longest < 1600) {
            $target = $max;
        }

        if ($target !== null) {
            $scale = $target / $longest;
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
