<?php

namespace App\Services;

use App\Models\DailyClosing;
use Carbon\Carbon;
use Illuminate\Http\Exceptions\HttpResponseException;

class ClosingService
{
    public const SECTOR_INVENTORY = 'inventory';
    public const SECTOR_FINANCE = 'finance';

    /**
     * آخر يوم مقفل لقطاع معين (أو null).
     */
    public static function lastClosedDate(int $companyId, string $sector): ?Carbon
    {
        $row = DailyClosing::where('company_id', $companyId)
            ->where('sector', $sector)
            ->where('status', 'closed')
            ->max('closing_date');

        return $row ? Carbon::parse($row)->startOfDay() : null;
    }

    /**
     * هل التاريخ مقفل فعلاً (<= آخر يوم مقفل)؟
     */
    public static function isClosed(int $companyId, string $sector, string $date): bool
    {
        $last = self::lastClosedDate($companyId, $sector);

        return $last !== null && Carbon::parse($date)->startOfDay()->lte($last);
    }

    /**
     * إقفال يوم (قفل). لا يمكن إقفال يوم قبل يوم مقفل لاحق.
     */
    public static function closeDay(int $companyId, string $sector, string $date, ?int $userId = null, ?string $notes = null): DailyClosing
    {
        $date = Carbon::parse($date)->startOfDay();

        return DailyClosing::updateOrCreate(
            [
                'company_id' => $companyId,
                'sector' => $sector,
                'closing_date' => $date,
            ],
            [
                'status' => 'closed',
                'closed_by' => $userId,
                'notes' => $notes,
            ]
        );
    }

    /**
     * فتح يوم (وحذف أي أقفال لاحقة لتفادي الفجوات) للسماح بالتعديل ثم إعادة الإقفال.
     */
    public static function reopenDay(int $companyId, string $sector, string $date): void
    {
        $date = Carbon::parse($date)->startOfDay();

        DailyClosing::where('company_id', $companyId)
            ->where('sector', $sector)
            ->where('closing_date', '>=', $date)
            ->delete();
    }

    /**
     * يرمي استثناءً (422) إذا كان التاريخ ضمن فترة مقفلة. يُستخدم قبل الترحيل/التعديل.
     */
    public static function ensureNotClosed(int $companyId, string $sector, ?string $date): void
    {
        if ($date === null) {
            return;
        }

        if (self::isClosed($companyId, $sector, $date)) {
            $last = self::lastClosedDate($companyId, $sector)?->format('Y-m-d');
            throw new HttpResponseException(response()->json([
                'message' => "لا يمكن التعديل: اليوم مقفل حتى $last (قطاع $sector). افتح اليوم أولاً من شاشة الإقفال اليومي.",
                'closed_until' => $last,
                'sector' => $sector,
            ], 422));
        }
    }
}
