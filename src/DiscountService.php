<?php

namespace teamatrk\UserDiscounts;

use teamatrk\UserDiscounts\Models\Discount;
use teamatrk\UserDiscounts\Models\UserDiscount;
use teamatrk\UserDiscounts\Models\DiscountAudit;
use teamatrk\UserDiscounts\Events\DiscountAssigned;
use teamatrk\UserDiscounts\Events\DiscountRevoked;
use teamatrk\UserDiscounts\Events\DiscountApplied;
use Illuminate\Support\Facades\Event;
use Illuminate\Database\Eloquent\Model;

class DiscountService
{
    public function assign(Model $user, Discount $discount, array $options = []): UserDiscount
    {
        $userDiscount = UserDiscount::create([
            'user_id' => $user->id,
            'discount_id' => $discount->id,
            'expires_at' => $options['expires_at'] ?? null,
        ]);

        Event::dispatch(new DiscountAssigned($userDiscount));

        return $userDiscount;
    }

    public function revoke(Model $user, Discount $discount): bool
    {
        $userDiscount = UserDiscount::where('user_id', $user->id)
            ->where('discount_id', $discount->id)
            ->first();

        if (!$userDiscount) {
            return false;
        }

        $userDiscount->delete();

        Event::dispatch(new DiscountRevoked($userDiscount));

        return true;
    }

    public function eligibleFor(Model $user, Discount $discount): bool
    {
        return UserDiscount::where('user_id', $user->id)
            ->where('discount_id', $discount->id)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
            })
            ->exists();
    }

    public function apply(Model $user, float $originalPrice): float
    {
        $discounts = $this->getUserDiscounts($user);

        if ($discounts->isEmpty()) {
            return $originalPrice;
        }

        // Sort by config order (2 types are supported)
        $stackingOrder = config('discounts.stacking_order', ['percentage', 'fixed']);
        $discounts = $discounts->sortBy(function ($userDiscount) use ($stackingOrder) {
            $type = $userDiscount->discount->type;
            return array_search($type, $stackingOrder) ?? PHP_INT_MAX;
        });

        $discountedPrice = $originalPrice;
        $totalDiscount = 0;




        foreach ($discounts as $userDiscount) {

            if (!$userDiscount->isUsable()) { // checks if usage cap is reached
                continue;
            }
            
            $discount = $userDiscount->discount;
            if ($discount->type === 'percentage') {
                $discountAmount = $discountedPrice * ($discount->value / 100);
            } else { // fixed
                $discountAmount = $discount->value;
            }

            $discountedPrice -= $discountAmount;
            $totalDiscount += $discountAmount;
            $userDiscount->increment('uses_count');
            DiscountAudit::create([
                'user_id' => $user->id,
                'discount_id' => $discount->id,
                'original_price' => $originalPrice,
                'applied_discount' => $discountAmount,
            ]);

            Event::dispatch(new DiscountApplied($userDiscount, $originalPrice, $discountedPrice));
        }

        // Apply max cap
        $maxCap = config('discounts.max_percentage_cap', 100);
        $maxDiscount = $originalPrice * ($maxCap / 100);
        if ($totalDiscount > $maxDiscount) {
            $discountedPrice = $originalPrice - $maxDiscount;
        }

        // Rounding
        $rounding = config('discounts.rounding', 'nearest');
        switch ($rounding) {
            case 'up':
                $discountedPrice = ceil($discountedPrice);
                break;
            case 'down':
                $discountedPrice = floor($discountedPrice);
                break;
            case 'nearest':
            default:
                $discountedPrice = round($discountedPrice);
                break;
        }

        return max($discountedPrice, 0); 
    }

    protected function getUserDiscounts(Model $user)
    {
        return UserDiscount::with('discount')
            ->where('user_id', $user->id)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
            })
            ->get();
    }

    public function remainingUses(Model $user, Discount $discount): int
    {
        $userDiscount = UserDiscount::where('user_id', $user->id)
            ->where('discount_id', $discount->id)
            ->first();
        if(empty($userDiscount)){
            return 0;
        }
        return $userDiscount->remainingUses();
    }

}