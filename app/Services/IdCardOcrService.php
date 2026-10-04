<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use RuntimeException;

/**
 * قراءة صور بطاقات الهوية إلى نص خام عبر محرك سحابي (Google Vision أو Gemini)
 * أو Tesseract. التدقيق واستخراج الحقول يتم في IdCardParser.
 */
class IdCardOcrService
{
    /**
     * تحويل صورة البطاقة إلى نص.
     *
     * @throws RuntimeException عند غياب كل المحركات أو فشل القراءة
     */
    public function recognize(UploadedFile $file): string
    {
        $original = $file->getRealPath();
        if ($original === null || !is_file($original)) {
            throw new RuntimeException('تعذّر الوصول إلى صورة البطاقة');
        }

        // محرك سحابي إن كان مُفعَّلاً ومفتاحه مسجّلاً، مع رجوع تلقائي لـ Tesseract.
        $cloud = $this->resolveCloudProvider();
        if ($cloud !== null) {
            try {
                return $cloud === 'gemini'
                    ? $this->recognizeWithGemini($original)
                    : $this->recognizeWithGoogle($original);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning("{$cloud} OCR failed, falling back to tesseract", [
                    'error' => $e->getMessage(),
                ]);
                // فشل المحرك السحابي → نكمل على Tesseract بالأسفل.
            }
        }

        if (!function_exists('exec')) {
            throw new RuntimeException('دالة exec معطّلة على الخادم، لا يمكن تشغيل محرك القراءة');
        }

        $binary = $this->resolveBinary();
        if ($binary === null) {
            throw new RuntimeException('محرك قراءة Tesseract غير مثبت على الخادم');
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
     * قراءة الصورة عبر Google Cloud Vision API (TEXT_DETECTION).
     *
     * @throws RuntimeException عند غياب curl أو فشل الاتصال أو ردّ خطأ
     */
    private function recognizeWithGoogle(string $path): string
    {
        if (!function_exists('curl_init')) {
            throw new RuntimeException('امتداد curl غير مثبت على الخادم');
        }

        $image = @file_get_contents($path);
        if ($image === false) {
            throw new RuntimeException('تعذّر قراءة ملف الصورة');
        }

        $payload = json_encode([
            'requests' => [[
                'image' => ['content' => base64_encode($image)],
                'features' => [[
                    'type' => 'TEXT_DETECTION',
                    'maxResults' => 1,
                ]],
                'imageContext' => [
                    'languageHints' => config('ocr.google_language_hints', ['ara', 'en']),
                ],
            ]],
        ]);

        $url = 'https://vision.googleapis.com/v1/images:annotate?key='
            . urlencode((string) config('ocr.google_api_key'));

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false) {
            throw new RuntimeException('تعذّر الاتصال بخدمة Google Vision: ' . $error);
        }

        $data = json_decode($response, true);
        if (!is_array($data)) {
            throw new RuntimeException('ردّ غير متوقع من Google Vision (HTTP ' . $status . ')');
        }

        if (isset($data['error'])) {
            throw new RuntimeException(
                'Google Vision: ' . ($data['error']['message'] ?? 'خطأ غير معروف')
            );
        }

        $text = trim((string) ($data['responses'][0]['fullTextAnnotation']['text'] ?? ''));
        if ($text === '') {
            throw new RuntimeException('لم يتم العثور على نص في الصورة');
        }

        return $text;
    }

    /**
     * اختيار المحرك السحابي المُفعَّل والمفتاحه موجود، أو null لاستخدام Tesseract.
     */
    private function resolveCloudProvider(): ?string
    {
        $provider = (string) config('ocr.provider', 'tesseract');

        if ($provider === 'google' && (string) config('ocr.google_api_key', '') !== '') {
            return 'google';
        }

        if ($provider === 'gemini' && (string) config('ocr.gemini_api_key', '') !== '') {
            return 'gemini';
        }

        return null;
    }

    /**
     * قراءة الصورة عبر Google Gemini API (generateContent مع الصورة مضمّنة).
     *
     * @throws RuntimeException عند غياب curl أو فشل الاتصال أو ردّ خطأ
     */
    private function recognizeWithGemini(string $path): string
    {
        if (!function_exists('curl_init')) {
            throw new RuntimeException('امتداد curl غير مثبت على الخادم');
        }

        $image = @file_get_contents($path);
        if ($image === false) {
            throw new RuntimeException('تعذّر قراءة ملف الصورة');
        }

        $mime = function_exists('mime_content_type') ? (mime_content_type($path) ?: '') : '';
        if ($mime === '' || !str_starts_with($mime, 'image/')) {
            $mime = 'image/jpeg';
        }

        $payload = json_encode([
            'contents' => [[
                'parts' => [
                    ['text' => (string) config('ocr.gemini_prompt', $this->geminiPrompt())],
                    ['inline_data' => [
                        'mime_type' => $mime,
                        'data' => base64_encode($image),
                    ]],
                ],
            ]],
            'generationConfig' => [
                'temperature' => 0.0,
                'maxOutputTokens' => 4096,
            ],
        ], JSON_UNESCAPED_UNICODE);

        $model = rawurlencode((string) config('ocr.gemini_model', 'gemini-2.5-flash'));
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/'
            . $model . ':generateContent?key='
            . urlencode((string) config('ocr.gemini_api_key'));

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => (int) config('ocr.gemini_timeout', 45),
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false) {
            throw new RuntimeException('تعذّر الاتصال بخدمة Gemini: ' . $error);
        }

        $data = json_decode($response, true);
        if (!is_array($data)) {
            throw new RuntimeException('ردّ غير متوقع من Gemini (HTTP ' . $status . ')');
        }

        if (isset($data['error'])) {
            throw new RuntimeException(
                'Gemini: ' . ($data['error']['message'] ?? 'خطأ غير معروف')
            );
        }

        $parts = $data['candidates'][0]['content']['parts'] ?? [];
        $text = '';
        foreach ((array) $parts as $part) {
            $text .= (string) ($part['text'] ?? '');
        }

        $text = trim($this->stripCodeFences($text));
        if ($text === '') {
            $blocked = $data['promptFeedback']['blockReason'] ?? null;
            throw new RuntimeException(
                $blocked
                    ? 'رُفض الطلب بواسطة Gemini: ' . $blocked
                    : 'لم يتم العثور على نص في الصورة'
            );
        }

        return $text;
    }

    /**
     * نص التعليم الافتراضي لـ Gemini: نسخ حرفياً لمحتوى البطاقة دون تعليق.
     */
    private function geminiPrompt(): string
    {
        return 'انسخ النص الظاهر في صورة بطاقة الهوية هذه حرفياً سطراً بسطر '
            . '(عربي وإنجليزي كما هو مكتوب)، دون أي تعليق أو وصف أو تنسيق أو ترجمة. '
            . 'أعد النص فقط.';
    }

    /**
     * إزالة أحوار ``` إن أضافها النموذج حول الناتج.
     */
    private function stripCodeFences(string $text): string
    {
        $text = preg_replace('/^```[a-zA-Z]*\s*\n?/', '', $text) ?? $text;
        $text = preg_replace('/\n?```\s*$/', '', $text) ?? $text;

        return $text;
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
     * تجهيز الصورة للقراءة: تدوير حسب EXIF + تصغير الصور الكبيرة فقط.
     * لا نستخدم التدرج الرمادي ولا تكبير الصور الصغيرة — كلاهما يقلّل
     * دقة Tesseract على صور البطاقات منخفضة الدقة.
     * يعيد مسار الملف المؤقت الجديد، أو المسار الأصلي إن لم تكن بحاجة معالجة.
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

        $oriented = $this->applyExifOrientation($image, $path);

        $max = max(400, (int) config('ocr.max_dimension', 2400));
        $width = imagesx($oriented);
        $height = imagesy($oriented);
        $longest = max($width, $height);

        // لا حاجة لأي معالجة: الصورة لم تُدوَّر ومقاسها ضمن الحد الأقصى.
        if ($oriented === $image && $longest <= $max) {
            imagedestroy($image);
            return $path;
        }

        if ($longest > $max) {
            $scale = $max / $longest;
            $newWidth = max(1, (int) round($width * $scale));
            $newHeight = max(1, (int) round($height * $scale));
            $resized = imagecreatetruecolor($newWidth, $newHeight);
            imagecopyresampled(
                $resized,
                $oriented,
                0,
                0,
                0,
                0,
                $newWidth,
                $newHeight,
                $width,
                $height
            );
            if ($oriented !== $image) {
                imagedestroy($oriented);
            }
            imagedestroy($image);
            $image = $resized;
        } else {
            $image = $oriented;
        }

        $temp = tempnam(sys_get_temp_dir(), 'idcard_ocr_');
        if ($temp === false) {
            imagedestroy($image);
            return $path;
        }

        $jpgPath = $temp . '.jpg';
        @unlink($temp);
        imagejpeg($image, $jpgPath, 95);
        imagedestroy($image);

        return is_file($jpgPath) ? $jpgPath : $path;
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
