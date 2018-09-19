<?php

use Faker\Generator as Faker;

$factory->define(Models\Person::class, function (Faker $faker) {
    return [
        'name' => $faker->name,
    ];
});

$factory->state(Models\Person::class, 'deleted_recent', function ($faker) {
    return [
        'deleted_at' => now()->subDays(rand(1, 30)),
    ];
});

$factory->state(Models\Person::class, 'deleted_old', function ($faker) {
    return [
        'deleted_at' => now()->subDays(rand(31, 100)),
    ];
});
