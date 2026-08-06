<?php

namespace App\Services;

use App\Models\NumberSeries;
use Illuminate\Database\Eloquent\Model;

/**
 * Abstract base class for all ERP documents.
 *
 * Provides a unified lifecycle for:
 *   SalesInvoice, PurchaseInvoice, PaymentVoucher,
 *   ReceiptVoucher, OpeningBalanceDocument, JournalEntry, etc.
 *
 * Lifecycle:
 *   draft → approved → posted → cancelled
 *   posted → cancelled (reverse)
 *   cancelled → draft (reopen, if allowed)
 */
abstract class Document extends Model
{
    // ─── Status Constants ──────────────────────────────────────

    const STATUS_DRAFT     = 'draft';
    const STATUS_APPROVED  = 'approved';
    const STATUS_POSTED    = 'posted';
    const STATUS_CANCELLED = 'cancelled';

    // ─── Abstract Methods (each document implements its own) ───

    /** Business validation beyond Laravel rules */
    abstract protected function validateBusinessRules(): void;

    /** Apply side effects on approve (update balances, etc.) */
    abstract protected function onApprove(): void;

    /** Apply side effects on post (update GL, stock, etc.) */
    abstract protected function onPost(): void;

    /** Reverse side effects on cancel (reverse stock, GL, etc.) */
    abstract protected function onCancel(): void;

    /** Reverse side effects on reopen (re-apply previous state) */
    abstract protected function onReopen(): void;

    /** Get the document type key for NumberSeries */
    abstract protected function documentType(): string;

    /** Get the human-readable document number field name */
    abstract protected function numberField(): string;

    // ─── Lifecycle Methods ─────────────────────────────────────

    /**
     * Save a new document (initial state = draft).
     */
    public function saveDocument(): static
    {
        if (is_null($this->getAttribute($this->numberField()))) {
            $this->setAttribute($this->numberField(), $this->generateNumber());
        }

        if (!isset($this->attributes['status'])) {
            $this->setAttribute('status', static::STATUS_DRAFT);
        }

        $this->save();

        return $this;
    }

    /**
     * Approve the document (draft → approved).
     */
    public function approve(): static
    {
        $this->assertStatus(static::STATUS_DRAFT);
        $this->validateBusinessRules();

        $this->onApprove();
        $this->update(['status' => static::STATUS_APPROVED]);

        return $this;
    }

    /**
     * Post the document (approved or draft → posted).
     * This is the final accounting state — stock, GL, and balances are updated.
     */
    public function post(): static
    {
        $this->assertStatus([static::STATUS_DRAFT, static::STATUS_APPROVED]);
        $this->validateBusinessRules();

        $this->onPost();
        $this->update([
            'status' => static::STATUS_POSTED,
            'posted_at' => now(),
        ]);

        return $this;
    }

    /**
     * Cancel the document (posted or approved → cancelled).
     * Reverses all side effects.
     */
    public function cancel(): static
    {
        $this->assertStatus([static::STATUS_APPROVED, static::STATUS_POSTED]);
        $this->onCancel();
        $this->update(['status' => static::STATUS_CANCELLED]);

        return $this;
    }

    /**
     * Reopen a cancelled document (cancelled → draft).
     */
    public function reopen(): static
    {
        $this->assertStatus(static::STATUS_CANCELLED);
        $this->onReopen();
        $this->update(['status' => static::STATUS_DRAFT]);

        return $this;
    }

    // ─── Number Generation ─────────────────────────────────────

    /**
     * Generate the next document number from NumberSeries.
     */
    public function generateNumber(): string
    {
        $companyId = $this->getAttribute('company_id');
        $documentType = $this->documentType();

        // Check if model has its own number generation (booted() method)
        // If so, use that as fallback
        $branchId = $this->getAttribute('branch_id') ?? null;
        $branchCode = null;

        if ($branchId && method_exists($this, 'branch')) {
            $branch = $this->branch;
            $branchCode = $branch?->code ?? null;
        }

        return NumberSeries::nextNumber(
            companyId: (int) $companyId,
            documentType: $documentType,
            branchId: $branchId !== null ? (int) $branchId : null,
        );
    }

    // ─── Helpers ───────────────────────────────────────────────

    protected function assertStatus(string|array $allowed): void
    {
        $allowed = (array) $allowed;
        $current = $this->getAttribute('status');

        if (!in_array($current, $allowed, true)) {
            $actual = class_basename(static::class);
            throw new \DomainException(
                "Cannot perform this action on '{$actual}' with status '{$current}'. " .
                "Expected: " . implode(' or ', $allowed)
            );
        }
    }

    public function isDraft(): bool
    {
        return $this->getAttribute('status') === static::STATUS_DRAFT;
    }

    public function isApproved(): bool
    {
        return $this->getAttribute('status') === static::STATUS_APPROVED;
    }

    public function isPosted(): bool
    {
        return $this->getAttribute('status') === static::STATUS_POSTED;
    }

    public function isCancelled(): bool
    {
        return $this->getAttribute('status') === static::STATUS_CANCELLED;
    }
}
