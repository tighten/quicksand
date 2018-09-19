<?php

use Faker\Generator as Faker;

$factory->define(Models\Thing::class, function (Faker $faker) {
    return [
        'name' => $faker->name,
    ];
});

$factory->state(Models\Thing::class, 'deleted_recent', function ($faker) {
    return [
        'deleted_at' => now()->subDays(rand(1, 30)),
    ];
});

$factory->state(Models\Thing::class, 'deleted_old', function ($faker) {
    return [
        'deleted_at' => now()->subDays(rand(31, 100)),
    ];
});
