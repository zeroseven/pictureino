<?php
defined('TYPO3') || die('🖼️');

// Add crop variants
$GLOBALS['TCA']['sys_file_reference']['columns']['crop']['config']['type'] = 'imageManipulation';

// Add allowed aspect ratios
$GLOBALS['TCA']['sys_file_reference']['columns']['crop']['config']['cropVariants']['default'] = [
    'title' => 'LLL:EXT:core/Resources/Private/Language/locallang_wizards.xlf:imwizard.crop_variant.default',
    'allowedAspectRatios' => [
        'NaN' => [
            'title' => 'LLL:EXT:core/Resources/Private/Language/locallang_wizards.xlf:imwizard.ratio.free',
            'value' => 0.0
        ]
    ],
    'focusArea' => [
        'x' => 1 / 3,
        'y' => 1 / 3,
        'width' => 1 / 3,
        'height' => 1 / 3
    ]
];
