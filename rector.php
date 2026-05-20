<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Ssch\TYPO3Rector\Set\Typo3SetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/Classes',
    ])
    ->withSets([
        Typo3SetList::TYPO3_14,
    ])
    ->withImportNames()
    ->withSkip([
        \Rector\Php55\Rector\Class_\ClassConstantToSelfClassRector::class,
    ]);
