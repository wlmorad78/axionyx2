<?php

namespace App\Services;

/**
 * استخراج حقول بطاقة الهوية (رقم قومي / اسم / تاريخ ميلاد / عنوان)
 * من النص الخام الذي يعيده Tesseract، مع تطبيع الأرقام والتحقق الهيكلي.
 */
class IdCardParser
{
    /** أكواد المحافظات المعتمدة في الرقم القومي */
    private const GOVERNORATE_CODES = [
        1, 2, 3, 4,
        11, 12, 13, 14, 15, 16, 17, 18, 19,
        21, 22, 23, 24, 25, 26, 27, 28, 29,
        31, 32, 33, 34, 35,
        88,
    ];

    /** أسطر لا تصلح للاسم (ترويسات وعناوين حقول البطاقة) */
    private const NOISE = [
        'جمهورية مصر العربية',
        'بطاقة تحقيق شخصية',
        'البطاقة الشخصية',
        'تاريخ الميلاد',
        'تاريخ الانتهاء',
        'تاريخ الإصدار',
        'رقم البطاقة',
        'الرقم القوم',
        'الحالة الاجتماعية',
        'الأحوال المدنية',
        'المهنة',
        'الديانة',
        'اسم الأب',
        'النوع',
        'الميلاد',
        'تنتهي',
    ];

    private const ADDRESS_MARKERS = [
        'شارع',
        'المديرية',
        'المركز',
        'محافظة',
        'عمارات',
        'دور',
        'شقة',
        'فيلا',
        'عمارة',
    ];

    private const GOVERNORATE_NAMES = [
        'القاهرة',
        'الإسكندرية',
        'بورسعيد',
        'السويس',
        'دمياط',
        'الدقهلية',
        'الشرقية',
        'القليوبية',
        'كفر الشيخ',
        'الغربية',
        'المنوفية',
        'البحيرة',
        'الإسماعيلية',
        'الجيزة',
        'بني سويف',
        'الفيوم',
        'المنيا',
        'أسيوط',
        'سوهاج',
        'قنا',
        'الأقصر',
        'البحر الأحمر',
        'الوادي الجديد',
        'مطروح',
        'شمال سيناء',
        'جنوب سيناء',
    ];

    /**
     * تحليل نص البطاقة الخام واستخراج الحقول.
     *
     * @return array{
     *     national_id: ?string,
     *     national_id_valid: bool,
     *     full_name: ?string,
     *     birth_date: ?string,
     *     address: ?string
     * }
     */
    public static function parse(string $text, string $side = 'front'): array
    {
        $text = self::normalizeDigits($text);
        $lines = self::lines($text);

        $nationalId = self::extractNationalId($lines, $text);
        $birthDate = self::extractBirthDate($lines);
        if ($birthDate === null && $nationalId !== null && self::isValidStructure($nationalId)) {
            $birthDate = self::birthDateFromNationalId($nationalId);
        }

        $positional = self::extractPositional($lines);

        return [
            'national_id' => $nationalId,
            'national_id_valid' => $nationalId !== null && self::isValidStructure($nationalId),
            'full_name' => $positional['name'] ?? self::extractName($lines),
            'birth_date' => $birthDate,
            'address' => $positional['address'] ?? self::extractAddress($lines),
        ];
    }

    /**
     * الاستخراج حسب مواضع الأسطر كما تُطبع على البطاقة:
     * السطران الأولان = الاسم، السطران التاليان = العنوان،
     * والسطر الأخير (الأرقام) = الرقم القومي.
     *
     * يُهمَل ما عدا هذه الأسطر (ترويسات، تسميات حقول، تواريخ، كود الصورة)
     * ويُهمل الناتج كله إن لم تتطابق البنية مع المتوقع.
     *
     * @return array{name: ?string, address: ?string}
     */
    private static function extractPositional(array $lines): array
    {
        $pool = [];
        foreach ($lines as $line) {
            if (self::isNoiseLine($line)) {
                continue;
            }

            $line = self::cleanValue(self::stripFieldLabel($line));
            if ($line === '') {
                continue;
            }
            if (!preg_match('/\p{Arabic}/u', $line)) {
                continue;
            }
            if (preg_match('/\d{14}/', $line)) {
                continue;
            }
            if (preg_match('/^\d{1,4}[\/\-.]\d{1,2}[\/\-.]\d{1,4}$/', $line)) {
                continue;
            }
            if (preg_match('/\d{6,}/', $line)) {
                continue;
            }

            $pool[] = $line;
        }

        if ($pool === []) {
            return ['name' => null, 'address' => null];
        }

        // الاسم: أول سطرين لا يبدوان عنواناً.
        $nameLines = [];
        foreach ($pool as $line) {
            if (self::isAddressLine($line)) {
                break;
            }
            $nameLines[] = $line;
            if (count($nameLines) === 2) {
                break;
            }
        }

        $name = null;
        if ($nameLines !== []) {
            $candidate = trim(implode(' ', $nameLines));
            if (self::looksLikeName($candidate)) {
                $name = $candidate;
            }
        }

        // العنوان: السطرين التاليان بعد الاسم (أو ما تبقّى من السطور).
        $address = null;
        $addressLines = array_slice($pool, count($nameLines), 2);
        if ($addressLines !== []) {
            $candidate = trim(implode(' - ', $addressLines));
            if (self::isAddressLine($candidate)) {
                $address = $candidate;
            }
        }

        return ['name' => $name, 'address' => $address];
    }

    /**
     * هل السطر ترويسة أو تسمية حقل يجب إهمالها في الاستخراج الموضعي؟
     */
    private static function isNoiseLine(string $line): bool
    {
        foreach (self::NOISE as $noise) {
            if (str_contains($line, $noise)) {
                return true;
            }
        }

        return false;
    }

    /**
     * إزالة تسميات الحقول (الاسم/العنوان/الرقم القومي/التاريخ) من بداية السطر.
     */
    private static function stripFieldLabel(string $value): string
    {
        $value = self::stripNameLabel($value);
        $stripped = preg_replace(
            '/^(?:العنوان|الرقم القوم[يى]|تاريخ الميلاد|تاريخ الإصدار|التاريخ|الميلاد|الحالة الاجتماعية|المهنة|الديانة|النوع)\s*[:：\-–—]?\s*/u',
            '',
            $value
        );

        return $stripped ?? $value;
    }

    /**
     * اشتقاق تاريخ الميلاد من الرقم القومي (خاصيتاه 2–6).
     */
    private static function birthDateFromNationalId(string $nationalId): string
    {
        $year = $nationalId[0] === '2'
            ? 1900 + (int) substr($nationalId, 1, 2)
            : 2000 + (int) substr($nationalId, 1, 2);

        return sprintf(
            '%04d-%02d-%02d',
            $year,
            (int) substr($nationalId, 3, 2),
            (int) substr($nationalId, 5, 2)
        );
    }

    /**
     * تحويل الأرقام العربية/الفارسية إلى أرقام لاتينية.
     */
    public static function normalizeDigits(string $text): string
    {
        $eastern = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $western = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

        return str_replace(
            [...$eastern, ...$persian],
            [...$western, ...$western],
            $text
        );
    }

    /**
     * التحقق الهيكلي للرقم القومي: 14 رقم + قرن + تاريخ ميلاد سليم + كود محافظة.
     */
    public static function isValidStructure(string $nationalId): bool
    {
        if (!preg_match('/^\d{14}$/', $nationalId)) {
            return false;
        }

        $century = $nationalId[0];
        if ($century === '2') {
            $year = 1900 + (int) substr($nationalId, 1, 2);
        } elseif ($century === '3') {
            $year = 2000 + (int) substr($nationalId, 1, 2);
        } else {
            return false;
        }

        $month = (int) substr($nationalId, 3, 2);
        $day = (int) substr($nationalId, 5, 2);
        if (!checkdate($month, $day, $year)) {
            return false;
        }

        $governorate = (int) substr($nationalId, 7, 2);
        return in_array($governorate, self::GOVERNORATE_CODES, true);
    }

    /**
     * فحص Luhn للخانة الأخيرة (مؤشر مساعد فقط — الخوارزمية الرسمية غير منشورة).
     */
    public static function passesLuhn(string $nationalId): bool
    {
        if (!preg_match('/^\d{14}$/', $nationalId)) {
            return false;
        }

        $digits = array_map('intval', str_split($nationalId));
        $check = array_pop($digits);
        $digits = array_reverse($digits);

        $sum = 0;
        foreach ($digits as $index => $value) {
            if ($index % 2 === 0) {
                $value *= 2;
                if ($value > 9) {
                    $value -= 9;
                }
            }
            $sum += $value;
        }

        return ((10 - ($sum % 10)) % 10) === $check;
    }

    private static function extractNationalId(array $lines, string $text): ?string
    {
        $candidates = [];

        foreach ($lines as $line) {
            // ربط الأرقام المفصولة بمسافات/شرطات داخل السطر نفسه
            $joined = preg_replace('/(?<=\d)[\s\-.]+(?=\d)/u', '', $line);
            if (!is_string($joined)) {
                continue;
            }
            if (preg_match_all('/\d{14}/', $joined, $matches)) {
                foreach ($matches[0] as $hit) {
                    $candidates[] = $hit;
                }
            }
        }

        if ($candidates === []) {
            // بديل: الرقم موزّع على أكثر من سطر
            $compact = preg_replace('/\s+/u', '', $text);
            if (is_string($compact) && preg_match_all('/\d{14,}/', $compact, $matches)) {
                foreach ($matches[0] as $hit) {
                    $candidates[] = substr($hit, 0, 14);
                }
            }
        }

        if ($candidates === []) {
            return null;
        }

        $candidates = array_values(array_unique($candidates));

        $structural = array_values(array_filter(
            $candidates,
            self::isValidStructure(...)
        ));
        $pool = $structural !== [] ? $structural : $candidates;

        $checksumOk = array_values(array_filter($pool, self::passesLuhn(...)));
        return $checksumOk !== [] ? $checksumOk[0] : $pool[0];
    }

    private static function extractName(array $lines): ?string
    {
        $count = count($lines);

        for ($i = 0; $i < $count; $i++) {
            $position = mb_strpos($lines[$i], 'الاسم');
            if ($position === false) {
                continue;
            }

            $value = self::cleanValue(
                self::stripNameLabel(mb_substr($lines[$i], $position + mb_strlen('الاسم')))
            );
            if (self::looksLikeName($value)) {
                return $value;
            }

            for ($j = $i + 1; $j < min($i + 3, $count); $j++) {
                $next = self::cleanValue(self::stripNameLabel($lines[$j]));
                if (self::looksLikeName($next)) {
                    return $next;
                }
            }
        }

        // بديل: أطول سطر عربي يشبه اسمًا
        $best = null;
        foreach ($lines as $line) {
            $candidate = self::cleanValue(self::stripNameLabel($line));
            if (!self::looksLikeName($candidate)) {
                continue;
            }
            if ($best === null || mb_strlen($candidate) > mb_strlen($best)) {
                $best = $candidate;
            }
        }

        return $best;
    }

    private static function extractBirthDate(array $lines): ?string
    {
        foreach ($lines as $line) {
            if (!str_contains($line, 'ميلاد')) {
                continue;
            }
            if (preg_match('/(\d{1,2})[-\/.](\d{1,2})[-\/.](\d{4})/', $line, $m)) {
                $day = (int) $m[1];
                $month = (int) $m[2];
                $year = (int) $m[3];
                if (checkdate($month, $day, $year)) {
                    return sprintf('%04d-%02d-%02d', $year, $month, $day);
                }
            }
        }

        return null;
    }

    private static function extractAddress(array $lines): ?string
    {
        $count = count($lines);

        for ($i = 0; $i < $count; $i++) {
            $position = mb_strpos($lines[$i], 'العنوان');
            if ($position === false) {
                continue;
            }

            $parts = [];
            $first = self::cleanValue(
                mb_substr($lines[$i], $position + mb_strlen('العنوان'))
            );
            if ($first !== '') {
                $parts[] = $first;
            }

            for ($j = $i + 1; $j < min($i + 4, $count); $j++) {
                $line = $lines[$j];
                if (!self::isAddressLine($line)) {
                    break;
                }
                $parts[] = self::cleanValue($line);
            }

            $address = trim(implode(' - ', array_filter($parts)));
            if (mb_strlen($address) >= 5) {
                return $address;
            }
        }

        // بديل: سطور تحمل مؤشرات عنوان أو أسماء محافظات
        $parts = [];
        foreach ($lines as $line) {
            if (!self::isAddressLine($line)) {
                continue;
            }
            $parts[] = self::cleanValue($line);
        }

        if ($parts === []) {
            return null;
        }

        $address = trim(implode(' - ', $parts));
        return mb_strlen($address) >= 5 ? $address : null;
    }

    /**
     * هل السطر يصلح ليكون جزءًا من العنوان؟
     */
    private static function isAddressLine(string $line): bool
    {
        if (mb_strlen(trim($line)) < 3) {
            return false;
        }

        foreach (self::NOISE as $noise) {
            if (str_contains($line, $noise)) {
                return false;
            }
        }

        foreach (self::ADDRESS_MARKERS as $marker) {
            if (str_contains($line, $marker)) {
                return true;
            }
        }

        foreach (self::GOVERNORATE_NAMES as $governorate) {
            if (str_contains($line, $governorate)) {
                return true;
            }
        }

        return false;
    }

    /**
     * هل يبدو النص اسم شخص (سطر عربي بلا أرقام وبلا ترويسات)؟
     *
     * يشترط كلمتين عربيتين حقيقيتين على الأقل حتى لا يمرّ نص القراءة
     * المشوّه (مثل "0. YVA A 64") كاسم.
     */
    private static function looksLikeName(string $value): bool
    {
        $value = self::cleanValue(self::stripNameLabel($value));
        if (mb_strlen($value) < 5) {
            return false;
        }

        foreach (self::NOISE as $noise) {
            if (str_contains($value, $noise)) {
                return false;
            }
        }

        foreach (['العنوان', 'شارع', 'المديرية', 'المركز', 'محافظة'] as $marker) {
            if (str_contains($value, $marker)) {
                return false;
            }
        }

        if (preg_match('/\d{3,}/', $value)) {
            return false;
        }

        if (!preg_match('/\p{Arabic}/u', $value)) {
            return false;
        }

        $words = preg_split('/\s+/u', $value, -1, PREG_SPLIT_NO_EMPTY);
        if (!is_array($words) || count($words) < 2) {
            return false;
        }

        $arabicWords = 0;
        $arabicLetters = 0;
        $totalLetters = 0;

        foreach ($words as $word) {
            if (mb_strlen($word) < 2) {
                continue;
            }
            if (mb_strlen($word) > 20) {
                return false;
            }

            preg_match_all('/\p{Arabic}/u', $word, $arabicMatches);
            preg_match_all('/\p{L}/u', $word, $letterMatches);
            $wordArabic = count($arabicMatches[0]);
            $wordLetters = count($letterMatches[0]);

            if ($wordArabic >= 2) {
                $arabicWords++;
            }
            $arabicLetters += $wordArabic;
            $totalLetters += $wordLetters;
        }

        if ($arabicWords < 2) {
            return false;
        }

        if ($totalLetters > 0 && $arabicLetters < (int) ceil($totalLetters * 0.4)) {
            return false;
        }

        return true;
    }

    /**
     * إزالة تسمية حقل "الاسم" الظاهرة في بداية المرشح.
     */
    private static function stripNameLabel(string $value): string
    {
        $stripped = preg_replace(
            '/^(?:الاسم(?:\s+الكامل|\s+الرباعي)?)\s*[:：\-–—]?\s*/u',
            '',
            trim($value)
        );

        return $stripped ?? trim($value);
    }

    private static function cleanValue(string $value): string
    {
        $value = preg_replace('/[\s\-\–\—\.,،:؛]+$/u', '', $value) ?? $value;
        $value = preg_replace('/^[\s\-\–\—\.,،:؛]+/u', '', $value) ?? $value;
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;
        return trim($value);
    }

    /**
     * @return string[]
     */
    private static function lines(string $text): array
    {
        if (!mb_check_encoding($text, 'UTF-8')) {
            $text = mb_scrub($text, 'UTF-8');
        }

        $lines = preg_split('/\R+/u', $text) ?: [];
        $lines = array_map('trim', $lines);
        $lines = array_values(array_filter($lines, fn ($line) => $line !== ''));

        return $lines;
    }
}
