<?php

use Faker\Generator as Faker;

$factory->define(Models\Car::class, function (Faker $faker) {
    return [
        'name' => $faker->name,
    ];
});

$factory->state(Models\Car::class, 'deleted_recent', function ($faker) {
    return [
        'deleted_at' => now()->subDays(rand(1, 30)),
    ];
});

$factory->state(Models\Car::class, 'deleted_old', function ($faker) {
    return [
        'deleted_at' => now()->subDays(rand(31, 100)),
    ];
});
