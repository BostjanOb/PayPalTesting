<?php

use Faker\Generator as Faker;

$factory->define(App\PayPal::class, function (Faker $faker) {
    return [
        'price'       => $faker->numberBetween(1, 50),
        'description' => $faker->text(),
    ];
});
