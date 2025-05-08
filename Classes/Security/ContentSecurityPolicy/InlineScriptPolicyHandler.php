<?php

declare(strict_types=1);

namespace Zeroseven\Pictureino\Security\ContentSecurityPolicy;

use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Directive;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Event\PolicyMutatedEvent;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\HashValue;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\SourceKeyword;
use Zeroseven\Pictureino\ViewHelpers\ImageViewHelper;

final class InlineScriptPolicyHandler
{
    public function __invoke(PolicyMutatedEvent $event): void
    {
        $currentPolicy = $event->getCurrentPolicy();
        $currentPolicy->extend(
            Directive::ScriptSrc,
            HashValue::hash(ImageViewHelper::ON_LOAD_EVENT),
            SourceKeyword::unsafeHashes
        );
    }
}
