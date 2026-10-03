<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use App\Services\IdCardOcrService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class IdCardOcrEndpointTest extends TestCase
{
    use RefreshDatabase;

    private const SAMPLE_OCR_TEXT = <<<'TEXT'
جمهورية مصر العربية
بطاقة تحقيق شخصية
الاسم
محمد أحمد علي محمود
تاريخ الميلاد 12-5-1998
٢٩٨٠٥١٢٠١٠١٢٣٤
TEXT;

    private function makeUser(): User
    {
        $company = Company::create([
            'code' => 'OCR-CO',
            'name_ar' => 'شركة اختبار',
            'is_active' => true,
        ]);

        $user = User::create([
            'usercode' => 'OCR1',
            'name' => 'مستخدم اختبار',
            'email' => 'ocr-test@example.com',
            'password' => bcrypt('secret'),
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        $user->companies()->attach($company->id);

        return $user;
    }

    private function fakeOcr(?string $text, ?\Throwable $error = null): object
    {
        return new class($text, $error) {
            public function __construct(
                private ?string $text,
                private ?\Throwable $error,
            ) {
            }

            public function recognize(UploadedFile $file): string
            {
                if ($this->error !== null) {
                    throw $this->error;
                }

                return (string) $this->text;
            }
        };
    }

    private function pngUpload(): UploadedFile
    {
        $png = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='
        );
        $path = tempnam(sys_get_temp_dir(), 'idcard_') . '.png';
        file_put_contents($path, $png);

        return new UploadedFile($path, 'card.png', 'image/png', null, true);
    }

    public function test_requires_authentication(): void
    {
        $this->postJson('/api/handheld/ocr-id-card')
            ->assertStatus(401);
    }

    public function test_validates_required_fields(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user, 'sanctum')
            ->post('/api/handheld/ocr-id-card', ['side' => 'front'])
            ->assertStatus(422);
    }

    public function test_rejects_invalid_side(): void
    {
        $user = $this->makeUser();
        $this->app->instance(IdCardOcrService::class, $this->fakeOcr(self::SAMPLE_OCR_TEXT));

        $this->actingAs($user, 'sanctum')
            ->post('/api/handheld/ocr-id-card', [
                'image' => $this->pngUpload(),
                'side' => 'middle',
            ])
            ->assertStatus(422);
    }

    public function test_returns_parsed_id_card_fields(): void
    {
        $user = $this->makeUser();
        $this->app->instance(IdCardOcrService::class, $this->fakeOcr(self::SAMPLE_OCR_TEXT));

        $response = $this->actingAs($user, 'sanctum')
            ->post('/api/handheld/ocr-id-card', [
                'image' => $this->pngUpload(),
                'side' => 'front',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.national_id', '29805120101234')
            ->assertJsonPath('data.national_id_valid', true)
            ->assertJsonPath('data.full_name', 'محمد أحمد علي محمود')
            ->assertJsonPath('data.birth_date', '1998-05-12');

        $this->assertNotEmpty($response->json('data.raw_text'));
    }

    public function test_returns_503_when_ocr_engine_unavailable(): void
    {
        $user = $this->makeUser();
        $this->app->instance(
            IdCardOcrService::class,
            $this->fakeOcr(null, new \RuntimeException('محرك قراءة Tesseract غير مثبت على الخادم'))
        );

        $this->actingAs($user, 'sanctum')
            ->post('/api/handheld/ocr-id-card', [
                'image' => $this->pngUpload(),
                'side' => 'front',
            ])
            ->assertStatus(503)
            ->assertJsonPath('success', false);
    }
}
