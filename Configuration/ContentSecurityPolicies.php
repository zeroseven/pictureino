<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Directive;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\HashValue;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Mutation;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\MutationCollection;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\MutationMode;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Scope;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\SourceKeyword;
use TYPO3\CMS\Core\Type\Map;
use Zeroseven\Pictureino\ViewHelpers\ImageViewHelper;

return Map::fromEntries(
    [
        Scope::frontend(),

        new MutationCollection(
            new Mutation(
                MutationMode::Extend,
                Directive::ScriptSrc,
                SourceKeyword::unsafeHashes
            ),

            new Mutation(
                MutationMode::Extend,
                Directive::ScriptSrc,
                HashValue::hash(ImageViewHelper::ON_LOAD_EVENT)
            ),
        ),
    ],
);
