<?php

namespace App\Services;

use App\Models\Item;
use App\Models\ItemUnit;
use App\Models\Unit;
use Illuminate\Support\Facades\Cache;

/**
 * Central Unit of Measure Conversion Engine.
 *
 * Single source of truth for ALL unit conversions across AXIONYX.
 * Every screen, report, and transaction MUST use this service.
 *
 * Rules:
 *  - Stock is ALWAYS stored in base units internally.
 *  - Base unit = the unit with is_default=1 (or conversion_factor=1 fallback).
 *  - conversion_factor: how many base units = 1 of this unit.
 *    e.g. 1 carton = 500 base units → conversion_factor = 500
 */
class UnitConversionService
{
    // ─── Resolve Base Unit ────────────────────────────────────

    /**
     * Get the base ItemUnit for an item (conversion_factor=1 first, then is_default=1 fallback).
     */
    public function getBaseUnit(int $itemId): ?ItemUnit
    {
        return ItemUnit::where('item_id', $itemId)
            ->where('conversion_factor', 1)
            ->whereNull('deleted_at')
            ->first()
            ?? ItemUnit::where('item_id', $itemId)
                ->where('is_default', true)
                ->whereNull('deleted_at')
                ->first()
            ?? ItemUnit::where('item_id', $itemId)
                ->whereNull('deleted_at')
                ->first();
    }

    /**
     * Get the base unit ID for an item.
     */
    public function getBaseUnitId(int $itemId): ?int
    {
        return $this->getBaseUnit($itemId)?->unit_id;
    }

    // ─── Resolve Unit ─────────────────────────────────────────

    /**
     * Resolve which ItemUnit to use for a given item + optional unit_id.
     * If unit_id is null or not found, falls back to the default unit.
     */
    public function resolveUnit(int $itemId, ?int $unitId = null): ?ItemUnit
    {
        if ($unitId) {
            $iu = ItemUnit::where('item_id', $itemId)
                ->where('unit_id', $unitId)
                ->whereNull('deleted_at')
                ->first();

            if ($iu) return $iu;
        }

        return $this->getBaseUnit($itemId);
    }

    // ─── Get Conversion Factor ────────────────────────────────

    /**
     * Get the conversion factor for a specific unit of an item.
     * Returns 1 if not found (safe fallback).
     */
    public function getConversionFactor(int $itemId, int $unitId): float
    {
        $iu = ItemUnit::where('item_id', $itemId)
            ->where('unit_id', $unitId)
            ->whereNull('deleted_at')
            ->first();

        if ($iu && $iu->conversion_factor > 0) {
            return (float) $iu->conversion_factor;
        }

        return 1.0;
    }

    // ─── Core Conversions ─────────────────────────────────────

    /**
     * Convert quantity from any unit → base units.
     *
     * Example: item has carton (cf=500), entered 3 cartons
     *   → toBase(itemId, cartonUnitId, 3) = 1500 base units
     */
    public function toBase(int $itemId, int $unitId, float $quantity): float
    {
        $factor = $this->getConversionFactor($itemId, $unitId);
        return $quantity * $factor;
    }

    /**
     * Convert quantity from base units → any unit.
     *
     * Example: item has carton (cf=500), stock=1500 base units
     *   → fromBase(itemId, cartonUnitId, 1500) = 3 cartons
     */
    public function fromBase(int $itemId, int $unitId, float $baseQuantity): float
    {
        $factor = $this->getConversionFactor($itemId, $unitId);
        if ($factor <= 0) return $baseQuantity;
        return $baseQuantity / $factor;
    }

    /**
     * Convert quantity from one unit to another unit.
     *
     * Example: 2 cartons → boxes (carton=500, box=10)
     *   → convert(itemId, cartonId, boxId, 2) = 100 boxes
     */
    public function convert(int $itemId, int $fromUnitId, int $toUnitId, float $quantity): float
    {
        $fromFactor = $this->getConversionFactor($itemId, $fromUnitId);
        $toFactor = $this->getConversionFactor($itemId, $toUnitId);

        if ($toFactor <= 0) return $quantity;

        $baseQty = $quantity * $fromFactor;
        return $baseQty / $toFactor;
    }

    // ─── Quantity Breakdown (for display) ─────────────────────

    /**
     * Break a total base quantity into human-readable unit breakdown.
     *
     * Example: 6789 base units with carton(500), box(10), piece(1)
     *   → [
     *       ['unit_id' => 1, 'unit_name' => 'كرتونة', 'conversion_factor' => 500, 'qty' => 13, 'remainder' => 289],
     *       ['unit_id' => 2, 'unit_name' => 'خرطوشة', 'conversion_factor' => 10, 'qty' => 28, 'remainder' => 9],
     *       ['unit_id' => 3, 'unit_name' => 'علبة', 'conversion_factor' => 1, 'qty' => 9, 'remainder' => 0],
     *     ]
     */
    public function breakdownQuantity(int $itemId, float $baseQty): array
    {
        $itemUnits = ItemUnit::where('item_id', $itemId)
            ->whereNull('deleted_at')
            ->with('unit')
            ->get()
            ->sortByDesc('conversion_factor')
            ->values();

        $breakdown = [];
        $remaining = (int) floor($baseQty);

        foreach ($itemUnits as $iu) {
            $cf = (int) floor((float) $iu->conversion_factor);
            if ($cf <= 0) continue;

            $count = intdiv($remaining, $cf);
            $remainder = $remaining - ($count * $cf);

            $breakdown[] = [
                'unit_id'          => $iu->unit_id,
                'unit_name'        => $iu->unit?->name_ar ?? '',
                'unit_name_en'     => $iu->unit?->name_en ?? '',
                'conversion_factor' => $cf,
                'stock'            => $count,
                'remainder'        => $remainder,
            ];

            $remaining = $remainder;
        }

        return $breakdown;
    }

    // ─── Validation Helpers ───────────────────────────────────

    /**
     * Check if an item has at least one unit defined.
     */
    public function hasUnits(int $itemId): bool
    {
        return ItemUnit::where('item_id', $itemId)
            ->whereNull('deleted_at')
            ->exists();
    }

    /**
     * Check if a given unit is valid for a specific item.
     */
    public function isValidUnit(int $itemId, int $unitId): bool
    {
        return ItemUnit::where('item_id', $itemId)
            ->where('unit_id', $unitId)
            ->whereNull('deleted_at')
            ->exists();
    }

    /**
     * Ensure every item has exactly one default unit.
     * Call this when saving item_units.
     */
    public function ensureSingleDefault(int $itemId, int $unitId): void
    {
        ItemUnit::where('item_id', $itemId)
            ->where('is_default', true)
            ->where('unit_id', '!=', $unitId)
            ->update(['is_default' => false]);
    }

    // ─── Bulk Helper (for queries) ────────────────────────────

    /**
     * Get a map of item_id → base_unit_id for a list of items.
     */
    public function getBaseUnitMap(array $itemIds): array
    {
        $defaults = ItemUnit::whereIn('item_id', $itemIds)
            ->where('is_default', true)
            ->whereNull('deleted_at')
            ->pluck('unit_id', 'item_id')
            ->toArray();

        $fallbacks = ItemUnit::whereIn('item_id', $itemIds)
            ->where('conversion_factor', 1)
            ->whereNull('deleted_at')
            ->pluck('unit_id', 'item_id')
            ->toArray();

        $result = [];
        foreach ($itemIds as $id) {
            $result[$id] = $defaults[$id] ?? $fallbacks[$id] ?? null;
        }

        return $result;
    }

    /**
     * Get a map of [item_id][unit_id] → conversion_factor for a list of items.
     */
    public function getConversionFactorMap(array $itemIds): array
    {
        $rows = ItemUnit::whereIn('item_id', $itemIds)
            ->whereNull('deleted_at')
            ->get(['item_id', 'unit_id', 'conversion_factor']);

        $map = [];
        foreach ($rows as $row) {
            $map[$row->item_id][$row->unit_id] = (float) $row->conversion_factor;
        }

        return $map;
    }
}
