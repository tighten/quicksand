<?php

return [
    // Days before deleting soft deleted content
    'days' => 30,

    // Whether to log the number of soft deleted records per model
    'log' => false,

    // List of models to run Quicksand on
    'models' => [
        // Example::class,
        // User::class => [
        //     'days' => '30' // per-model days setting override
        // ]
    ]
];
