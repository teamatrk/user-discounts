<?php

namespace teamatrk\UserDiscounts\Tests\Feature;

use teamatrk\UserDiscounts\DiscountService;
use teamatrk\UserDiscounts\Models\Discount;
use teamatrk\UserDiscounts\Models\UserDiscount;
use teamatrk\UserDiscounts\Events\DiscountAssigned;
use teamatrk\UserDiscounts\Events\DiscountRevoked;
use teamatrk\UserDiscounts\Events\DiscountApplied;
use teamatrk\UserDiscounts\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Mockery;

class DiscountFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_discount_assigned_event()
    {
        Event::fake();

        $user = Mockery::mock(\Illuminate\Database\Eloquent\Model::class)->makePartial();
        $user->id = 1;

        $discount = Discount::create(['name' => 'Test', 'type' => 'percentage', 'value' => 10]);

        app(DiscountService::class)->assign($user, $discount);

        Event::assertDispatched(DiscountAssigned::class);
    }

    public function test_discount_revoked_event()
    {
        Event::fake();

        $user = Mockery::mock(\Illuminate\Database\Eloquent\Model::class)->makePartial();
        $user->id = 1;

        $discount = Discount::create(['name' => 'Test', 'type' => 'percentage', 'value' => 10]);
        $userDiscount = UserDiscount::create(['user_id' => 1, 'discount_id' => $discount->id]);

        app(DiscountService::class)->revoke($user, $discount);

        Event::assertDispatched(DiscountRevoked::class);
    }

    public function test_discount_applied_event()
    {
        Event::fake();

        $user = Mockery::mock(\Illuminate\Database\Eloquent\Model::class)->makePartial();
        $user->id = 1;

        $discount = Discount::create(['name' => 'Test', 'type' => 'percentage', 'value' => 10]);
        app(DiscountService::class)->assign($user, $discount);

        app(DiscountService::class)->apply($user, 100.0);

        Event::assertDispatched(DiscountApplied::class);
    }


}