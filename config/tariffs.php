<?php

declare(strict_types=1);

return [

    // Тариф по умолчанию: активной подписки нет → free
    'default' => 'free',

    'plans' => [
        'free' => [
            'limits' => [
                'students' => 1,     // всего учеников, включая удалённых
                'lessons'  => null,  // null — без ограничений (задел на будущее)
                'topics'   => null,
            ],
            'price_month' => 0,
            'price_year'  => 0,
        ],
        'paid' => [
            'limits' => [
                'students' => null,
                'lessons'  => null,
                'topics'   => null,
            ],
            'price_month' => 100,
            'price_year'  => 1000,
        ],
    ],

];
