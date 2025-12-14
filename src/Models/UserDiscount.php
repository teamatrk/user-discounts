<?php

namespace teamatrk\UserDiscounts\Models;

use Illuminate\Database\Eloquent\Model;

class UserDiscount extends Model
{
    protected $fillable = ['user_id', 'discount_id', 'expires_at', 'uses_count'];

    public function discount()
    {
        return $this->belongsTo(Discount::class);
    }
    public function isUsable(): bool
    {
        $discount = $this->discount;

        if (!$discount->hasUnlimitedUses() && $this->uses_count >= $discount->usage_cap) {
            return false;
        }

        return true;
    }

    public function remainingUses(): int
    {
        if ($this->discount->hasUnlimitedUses()) {
            return PHP_INT_MAX;
        }

        return $this->discount->usage_cap - $this->uses_count;
    }
}