<?php

use Faker\Generator as Faker;

$factory->define(Models\GlobalScopedThing::class, function (Faker $faker) {
    return [
        'name' => $faker->name,
    ];
});

$factory->state(Models\GlobalScopedThing::class, 'global_scope_condition_met', function ($faker) {
    return [
        'name' => 'Global Scope Applied',
    ];
});

$factory->state(Models\GlobalScopedThing::class, 'deleted_old', function ($faker) {
    return [
        'deleted_at' => now()->subDays(rand(31, 100)),
    ];
});
