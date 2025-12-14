<?php

namespace teamatrk\UserDiscounts\Models;

use Illuminate\Database\Eloquent\Model;

class Discount extends Model
{
    protected $fillable = ['name', 'type', 'value' , 'usage_cap'];

    public function userDiscounts()
    {
        return $this->hasMany(UserDiscount::class);
    }
    public function hasUnlimitedUses(): bool
    {
        return $this->usage_cap === null;
    }

}