<?php

namespace teamatrk\UserDiscounts\Models;

use Illuminate\Database\Eloquent\Model;

class DiscountAudit extends Model
{
    protected $fillable = ['user_id', 'discount_id', 'original_price', 'applied_discount'];
}