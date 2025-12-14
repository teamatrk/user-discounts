# user-discounts
Reusable Laravel package for user-level discounts


Install 

composer require teamatrk/user-discounts

php artisan vendor:publish --provider="teamatrk\UserDiscounts\DiscountServiceProvider" --tag=config

php artisan vendor:publish --provider="teamatrk\UserDiscounts\DiscountServiceProvider" --tag=user-discounts-migrations

php artisan migrate



Usage 

$discountService = app(DiscountService::class);

Create a discount limited to 3 uses per user

$discount = Discount::create([

    'name'       => 'Winter Sale',
    'type'       => 'percentage',
    'value'      => 25,
    'usage_cap'  => 3,        //  per user cap
]);


$discountService->assign($user, $discount); // $user -> user model


Check remaining uses

echo $discountService->remainingUses($user, $discount); // -> 3


Apply multiple times

$discountService->apply($user, 100); -> 75
