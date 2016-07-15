<?php

return [
    // Days before delete soft deleted content
    'days' => 30,

    // Log the number of soft deleted records per model
    'log' => false,

    // List of models with soft deletes enabled
    'models' => [
        // Example::class,
        // User::class => [
        //     'days' => '30' // per-model days setting override
        // ]
    ]
];
