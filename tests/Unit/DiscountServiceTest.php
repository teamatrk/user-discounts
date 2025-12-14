<?php

namespace teamatrk\UserDiscounts\Tests\Unit;

use teamatrk\UserDiscounts\DiscountService;
use teamatrk\UserDiscounts\Models\Discount;
use teamatrk\UserDiscounts\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class DiscountServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(DiscountService::class);
    }

    public function test_assign_discount()
    {
        $user = Mockery::mock(\Illuminate\Database\Eloquent\Model::class)->makePartial();
        $user->id = 1;

        $discount = Discount::create(['name' => 'Test', 'type' => 'percentage', 'value' => 10]);

        $userDiscount = $this->service->assign($user, $discount);

        $this->assertDatabaseHas('user_discounts', ['user_id' => 1, 'discount_id' => $discount->id]);
    }

    public function test_revoke_discount()
    {
        $user = Mockery::mock(\Illuminate\Database\Eloquent\Model::class)->makePartial();
        $user->id = 1;

        $discount = Discount::create(['name' => 'Test', 'type' => 'percentage', 'value' => 10]);
        $this->service->assign($user, $discount);

        $result = $this->service->revoke($user, $discount);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('user_discounts', ['user_id' => 1, 'discount_id' => $discount->id]);
    }

    public function test_eligible_for_discount()
    {
        $user = Mockery::mock(\Illuminate\Database\Eloquent\Model::class)->makePartial();
        $user->id = 1;

        $discount = Discount::create(['name' => 'Test', 'type' => 'percentage', 'value' => 10]);
        $this->service->assign($user, $discount);

        $this->assertTrue($this->service->eligibleFor($user, $discount));
    }

    public function test_apply_discounts()
    {
        $user = Mockery::mock(\Illuminate\Database\Eloquent\Model::class)->makePartial();
        $user->id = 1;

        $discount1 = Discount::create(['name' => 'Percent', 'type' => 'percentage', 'value' => 20]);
        $discount2 = Discount::create(['name' => 'Fixed', 'type' => 'fixed', 'value' => 10]);

        $this->service->assign($user, $discount1);
        $this->service->assign($user, $discount2);

        config(['discounts.stacking_order' => ['percentage', 'fixed']]);
        config(['discounts.max_percentage_cap' => 100]);
        config(['discounts.rounding' => 'nearest']);

        $discountedPrice = $this->service->apply($user, 100.0);

        $this->assertEquals(70.0, $discountedPrice); // 100 - 20% = 80, then -10 = 70
        $this->assertDatabaseCount('discount_audits', 2);
    }

    public function test_apply_with_cap()
    {
        $user = Mockery::mock(\Illuminate\Database\Eloquent\Model::class)->makePartial();
        $user->id = 1;

        $discount = Discount::create(['name' => 'High Percent', 'type' => 'percentage', 'value' => 90]);

        $this->service->assign($user, $discount);

        config(['discounts.max_percentage_cap' => 50]);

        $discountedPrice = $this->service->apply($user, 100.0);

        $this->assertEquals(50.0, $discountedPrice); // Capped at 50%
    }

    public function test_per_user_usage_cap()
    {
        $user = Mockery::mock(\Illuminate\Database\Eloquent\Model::class)->makePartial();
        $user->id = 1;

        $discount = Discount::create([
            'name' => 'Limited',
            'type' => 'percentage',
            'value' => 50,
            'usage_cap' => 2,
        ]);

        $this->service->assign($user, $discount);

        $this->assertEquals(50.0, $this->service->apply($user, 100)); // 1st use
        $this->assertEquals(50.0, $this->service->apply($user, 100)); // 2nd use
        $this->assertEquals(100.0, $this->service->apply($user, 100)); // 3rd it will be skipped

        $this->assertEquals(0, $this->service->remainingUses($user, $discount));
    }
}