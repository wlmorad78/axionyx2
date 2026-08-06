<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;
use App\Models\Company\Company;
use App\Traits\BelongsToCompany;

class NumberSeries extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'document_type',
        'prefix',
        'format',
        'next_sequence',
        'padding',
        'separator',
        'include_branch',
        'include_year',
        'include_month',
        'is_active',
    ];

    protected $casts = [
        'include_branch' => 'boolean',
        'include_year' => 'boolean',
        'include_month' => 'boolean',
        'is_active' => 'boolean',
        'next_sequence' => 'integer',
        'padding' => 'integer',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the next number atomically (no lost numbers).
     * Uses DB transaction + locking to prevent race conditions.
     */
    public static function nextNumber(int $companyId, string $documentType, ?int $branchId = null, ?int $branchCode = null): string
    {
        return DB::transaction(function () use ($companyId, $documentType, $branchId, $branchCode) {
            $series = static::lockForUpdate()
                ->where('company_id', $companyId)
                ->where('document_type', $documentType)
                ->where('is_active', true)
                ->first();

            if (!$series) {
                // Auto-create default series
                $series = static::create([
                    'company_id' => $companyId,
                    'document_type' => $documentType,
                    'prefix' => strtoupper(substr($documentType, 0, 2)),
                    'format' => '{prefix}-{sequence}',
                    'next_sequence' => 1,
                    'padding' => 5,
                ]);
            }

            $number = $series->formatNumber($branchId, $branchCode);
            $series->increment('next_sequence');

            return $number;
        });
    }

    /**
     * Format the number using the format string.
     *
     * Supported placeholders:
     *   {prefix}       → series prefix (e.g. 'SI', 'INV')
     *   {sequence:N}   → zero-padded sequence (N = padding digits)
     *   {branch}       → branch code
     *   {year}         → 4-digit year
     *   {month}        → 2-digit month
     *   {company}      → company code
     */
    public function formatNumber(?int $branchId = null, ?int $branchCode = null): string
    {
        $seq = str_pad(
            (string) $this->next_sequence,
            $this->padding,
            '0',
            STR_PAD_LEFT
        );

        $replacements = [
            '{prefix}' => $this->prefix,
            '{sequence}' => $seq,
            '{year}' => date('Y'),
            '{month}' => date('m'),
        ];

        // Dynamic padding: {sequence:5}, {sequence:7}
        $format = preg_replace_callback('/\{sequence:(\d+)\}/', function ($m) use ($seq) {
            return str_pad((string) $this->next_sequence, (int) $m[1], '0', STR_PAD_LEFT);
        }, $this->format);

        // Branch placeholder
        if (str_contains($format, '{branch}') && $branchCode !== null) {
            $replacements['{branch}'] = (string) $branchCode;
        }

        // Company placeholder
        if (str_contains($format, '{company}')) {
            $company = Company::find($this->company_id);
            $replacements['{company}'] = $company?->code ?? '';
        }

        return str_replace(array_keys($replacements), array_values($replacements), $format);
    }

    /**
     * Get or create a series for a document type.
     */
    public static function findOrCreateForCompany(int $companyId, string $documentType, array $defaults = []): self
    {
        return static::firstOrCreate(
            ['company_id' => $companyId, 'document_type' => $documentType],
            array_merge([
                'prefix' => strtoupper(substr($documentType, 0, 2)),
                'format' => '{prefix}-{sequence}',
                'next_sequence' => 1,
                'padding' => 5,
            ], $defaults)
        );
    }
}
