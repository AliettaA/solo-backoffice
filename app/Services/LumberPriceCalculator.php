<?php

namespace App\Services;

use App\Models\LumberSpecies;
use App\Models\PricingSetting;
use App\Models\QuantityTierSurcharge;
use App\Models\ThicknessSurcharge;

class LumberPriceCalculator
{
    /**
     * 樹種・厚み・幅・長さ・数量帯から最終単価を計算する
     */
    public function calculateUnitPrice(
        LumberSpecies $species,
        float $thickness,
        float $width,
        float $length,
        QuantityTierSurcharge $tier
    ): int {
        $base = $this->basePrice($species, $width);
        $thicknessSurcharge = $species->apply_thickness_surcharge
            ? $this->thicknessSurcharge($thickness)
            : 0;
        $lengthAdjustment = $species->apply_length_adjustment
            ? $this->lengthAdjustment($length)
            : 0;

        return (int) round($base + $thicknessSurcharge + $lengthAdjustment + $tier->surcharge_amount);
    }

    /**
     * 樹種・幅から基本単価を取得
     */
    public function basePrice(LumberSpecies $species, float $width): int
    {
        if ($species->pricing_type === 'flat') {
            return (int) $species->flat_price;
        }

        // by_width: 完全一致する幅の単価を探す。見つからなければ一番近い幅で代用
        $exact = $species->widthPrices()->where('width', $width)->value('base_price');
        if ($exact !== null) {
            return (int) $exact;
        }

        $closest = $species->widthPrices()
            ->selectRaw('*, ABS(width - ?) as diff', [$width])
            ->orderBy('diff')
            ->first();

        return $closest ? (int) $closest->base_price : 0;
    }

    /**
     * 厚みから加算額を取得(該当する最大の閾値を採用、累積ではない)
     */
    public function thicknessSurcharge(float $thickness): int
    {
        $surcharge = ThicknessSurcharge::where('min_thickness', '<=', $thickness)
            ->orderByDesc('min_thickness')
            ->first();

        return $surcharge ? (int) $surcharge->surcharge_amount : 0;
    }

    /**
     * 長さから調整額を取得(基準長との差 × 1mあたりの単価)
     */
    public function lengthAdjustment(float $length): int
    {
        $baseLength = (float) PricingSetting::get('base_length_m', 4);
        $perMeter = (float) PricingSetting::get('price_per_meter_diff', 10);

        $minimum = (float) PricingSetting::get('base_price_min_length_m', min(3.7, $baseLength));
        if ($length >= $minimum && $length <= $baseLength) {
            return 0;
        }

        return (int) round(($length - $baseLength) * $perMeter);
    }

    /**
     * 数量に対応する数量帯を取得
     */
    public function findTierForQuantity(int $quantity): ?QuantityTierSurcharge
    {
        return QuantityTierSurcharge::where('min_qty', '<=', $quantity)
            ->where(function ($q) use ($quantity) {
                $q->whereNull('max_qty')->orWhere('max_qty', '>=', $quantity);
            })
            ->orderBy('sort_order')
            ->first();
    }
}