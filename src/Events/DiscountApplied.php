<?php

namespace teamatrk\UserDiscounts\Events;

use teamatrk\UserDiscounts\Models\UserDiscount;
use Illuminate\Foundation\Events\Dispatchable;

class DiscountApplied
{
    use Dispatchable;

    public $userDiscount;
    public $originalPrice;
    public $discountedPrice;

    public function __construct(UserDiscount $userDiscount, float $originalPrice, float $discountedPrice)
    {
        $this->userDiscount = $userDiscount;
        $this->originalPrice = $originalPrice;
        $this->discountedPrice = $discountedPrice;
    }
}