<?php

namespace teamatrk\UserDiscounts\Events;

use teamatrk\UserDiscounts\Models\UserDiscount;
use Illuminate\Foundation\Events\Dispatchable;

class DiscountRevoked
{
    use Dispatchable;

    public $userDiscount;

    public function __construct(UserDiscount $userDiscount)
    {
        $this->userDiscount = $userDiscount;
    }
}