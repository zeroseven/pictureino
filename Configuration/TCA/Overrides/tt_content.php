<?php

defined('TYPO3') || die('🖼️');

call_user_func(static function (string $table) {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns($table, [
        'aspect_ratio' => [
            'label' => 'aspect ratio',
            'l10n_mode' => 'exclude',
            'config' => \Zeroseven\Pictureino\Backend\Form\Element\AspectRatioElement::addTCAConfig()
        ],
    ]);

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addFieldsToPalette(
        $table,
        'mediaAdjustments',
        '--linebreak--,aspect_ratio',
        'after:imageborder'
    );
}, 'tt_content');
