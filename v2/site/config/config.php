<?php

return [
    'debug' => false,
    'languages' => true,
    'panel' => [
        'install' => false, // nur zum Anlegen des ersten Kontos auf true
        'language' => 'de',
    ],
    'date' => ['handler' => 'intl'],
    'thumbs' => [
        'quality' => 82,
        'srcsets' => [
            'standard' => [640, 1024, 1600],
        ],
    ],
];
