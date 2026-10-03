<?php

namespace Tests\Unit;

use App\Services\IdCardParser;
use PHPUnit\Framework\TestCase;

class IdCardParserTest extends TestCase
{
    private const FRONT_TEXT = <<<'TEXT'
جمهورية مصر العربية
بطاقة تحقيق شخصية
الاسم
محمد أحمد علي محمود
تاريخ الميلاد 12-5-1998
النوع: ذكر
٢٩٨٠٥١٢٠١٠١٢٣٤
TEXT;

    private const BACK_TEXT = <<<'TEXT'
بطاقة تحقيق شخصية
العنوان: القاهرة - مدينة نصر
شارع الترعة البولاقية 14
الحالة الاجتماعية: أعزب
TEXT;

    public function test_normalizes_eastern_and_persian_digits(): void
    {
        $this->assertSame('2980512', IdCardParser::normalizeDigits('٢٩٨٠٥١٢'));
        $this->assertSame('3012', IdCardParser::normalizeDigits('۳۰۱۲'));
    }

    public function test_extracts_national_id_from_front(): void
    {
        $result = IdCardParser::parse(self::FRONT_TEXT, 'front');

        $this->assertSame('29805120101234', $result['national_id']);
        $this->assertTrue($result['national_id_valid']);
    }

    public function test_extracts_national_id_split_by_spaces(): void
    {
        $text = "الرقم القومي 2980 512 0101234\nتاريخ الميلاد 12-5-1998";
        $result = IdCardParser::parse($text, 'front');

        $this->assertSame('29805120101234', $result['national_id']);
    }

    public function test_prefers_structurally_valid_candidate(): void
    {
        // الرقم الثاني غير صالح (القرن 1 غير مسموح) فيجب تفضيل الأول
        $text = "12345678901234 و أيضاً 29805120101234";
        $result = IdCardParser::parse($text, 'front');

        $this->assertSame('29805120101234', $result['national_id']);
    }

    public function test_flags_invalid_structure_but_still_returns_id(): void
    {
        // يوم 32 غير صالح
        $text = 'الرقم القومي 29805320101234';
        $result = IdCardParser::parse($text, 'front');

        $this->assertSame('29805320101234', $result['national_id']);
        $this->assertFalse($result['national_id_valid']);
    }

    public function test_extracts_name_from_label_line(): void
    {
        $result = IdCardParser::parse(self::FRONT_TEXT, 'front');

        $this->assertSame('محمد أحمد علي محمود', $result['full_name']);
    }

    public function test_extracts_name_when_on_same_line_as_label(): void
    {
        $result = IdCardParser::parse('الاسم: محمد أحمد علي', 'front');

        $this->assertSame('محمد أحمد علي', $result['full_name']);
    }

    public function test_does_not_pick_header_or_address_as_name(): void
    {
        $text = "جمهورية مصر العربية\nالجيزة - الهرم - شارع الملك فيصل 5";
        $result = IdCardParser::parse($text, 'front');

        $this->assertNull($result['full_name']);
    }

    public function test_extracts_birth_date(): void
    {
        $result = IdCardParser::parse(self::FRONT_TEXT, 'front');

        $this->assertSame('1998-05-12', $result['birth_date']);
    }

    public function test_extracts_address_from_label_and_following_lines(): void
    {
        $result = IdCardParser::parse(self::BACK_TEXT, 'back');

        $this->assertSame(
            'القاهرة - مدينة نصر - شارع الترعة البولاقية 14',
            $result['address']
        );
    }

    public function test_extracts_address_without_label_via_markers(): void
    {
        $result = IdCardParser::parse('الجيزة - الهرم - شارع الملك فيصل 5', 'back');

        $this->assertSame('الجيزة - الهرم - شارع الملك فيصل 5', $result['address']);
    }

    public function test_returns_nulls_for_empty_text(): void
    {
        $result = IdCardParser::parse('', 'front');

        $this->assertNull($result['national_id']);
        $this->assertFalse($result['national_id_valid']);
        $this->assertNull($result['full_name']);
        $this->assertNull($result['birth_date']);
        $this->assertNull($result['address']);
    }

    public function test_luhn_rejects_documented_invalid_example(): void
    {
        $this->assertFalse(IdCardParser::passesLuhn('30201095501283'));
        $this->assertFalse(IdCardParser::passesLuhn('123'));
    }

    public function test_structure_validation_rejects_bad_values(): void
    {
        $this->assertFalse(IdCardParser::isValidStructure('123'));
        $this->assertFalse(IdCardParser::isValidStructure('19805120101234')); // القرن 1
        $this->assertFalse(IdCardParser::isValidStructure('2980512010123'));  // 13 رقم
        $this->assertTrue(IdCardParser::isValidStructure('29805120101234'));
    }

    public function test_rejects_garbage_name_after_label(): void
    {
        // نص قراءة مشوّه لا يحتوي اسمًا عربيًا حقيقياً
        $result = IdCardParser::parse('الاسم ٠. YVA A 64', 'front');

        $this->assertNull($result['full_name']);
    }

    public function test_strips_label_when_name_on_same_line(): void
    {
        $result = IdCardParser::parse('الاسم محمد أحمد علي محمود', 'front');

        $this->assertSame('محمد أحمد علي محمود', $result['full_name']);
    }

    public function test_derives_birth_date_from_national_id_when_not_printed(): void
    {
        $text = "الاسم محمد أحمد علي\nالرقم القومي 29805120101234";
        $result = IdCardParser::parse($text, 'front');

        $this->assertSame('1998-05-12', $result['birth_date']);
    }

    public function test_printed_birth_date_takes_precedence_over_national_id(): void
    {
        // تاريخ مطبوع مختلف عن المستخرج من الرقم القومي (سنة ميلاد 2001)
        $text = "الرقم القومي 29805120101234\nتاريخ الميلاد 3-7-2001";
        $result = IdCardParser::parse($text, 'front');

        $this->assertSame('2001-07-03', $result['birth_date']);
    }
}
