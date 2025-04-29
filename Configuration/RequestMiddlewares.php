<?php

return [
    'frontend' => [
        'zeroseven/pictureino/image' => [
            'target' => \Zeroseven\Pictureino\Middleware\ImageRequest::class,
            'before' => [
                'typo3/cms-frontend/base-redirect-resolver'
            ],
            'after' => [
                'typo3/cms-frontend/authentication'
            ]
        ]
    ]
];
