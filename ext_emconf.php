<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Pictureiño',
    'description' => 'Automated responseive images for TYPO3. Get perfect images without any effort.',
    'category' => 'fe',
    'version' => '0.10.0',
    'state' => 'beta',
    'author' => 'Raphael Thanner',
    'author_email' => '',
    'author_company' => 'Zeroseven',
    'constraints' => [
        'depends' => [
            'typo3' => '13.0.0-13.99.99',
            'php' => '8.2.0-8.99.99'
        ],
        'conflicts' => [],
        'suggests' => []
    ]
];
