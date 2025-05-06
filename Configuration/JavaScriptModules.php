<?php

return [
    'dependencies' => ['core', 'backend'],
    'imports' => [
        '@zeroseven/pictureino/' => [
            'path' => 'EXT:pictureino/Resources/Public/JavaScript/',
            'exclude' => [
                'EXT:pictureino/Resources/Public/JavaScript/Frontend/'
            ]
        ]
    ]
];
