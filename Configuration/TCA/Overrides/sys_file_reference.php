<?php
defined('TYPO3') || die('🖼️');

isset($GLOBALS['TCA']['sys_file_reference']['columns']['crop']['config']['cropVariants']['default'])
|| ($GLOBALS['TCA']['sys_file_reference']['columns']['crop']['config']['cropVariants']['default'] = [
    'title' => 'Responsive Image',
    'allowedAspectRatios' => [
        'NaN' => [
            'title' => 'LLL:EXT:core/Resources/Private/Language/locallang_wizards.xlf:imwizard.ratio.free',
            'value' => 0.0
        ]
    ],
]);

isset($GLOBALS['TCA']['sys_file_reference']['columns']['crop']['config']['cropVariants']['default']['focusArea'])
|| ($GLOBALS['TCA']['sys_file_reference']['columns']['crop']['config']['cropVariants']['default']['focusArea'] = [
    'x' => 1 / 3,
    'y' => 1 / 3,
    'width' => 1 / 3,
    'height' => 1 / 3
]);
