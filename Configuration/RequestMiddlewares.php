<?php

return [
    'frontend' => [
        'zeroseven/picturerino/image' => [
            'target' => \Zeroseven\Picturerino\Middleware\ImageRequest::class,
            'before' => [
                'typo3/cms-frontend/base-redirect-resolver'
            ],
            'after' => [
                'typo3/cms-frontend/authentication'
            ]
        ]
    ]
];
