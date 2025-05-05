<?php

declare(strict_types=1);

namespace Zeroseven\Pictureino\Utility;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Site\Entity\SiteSettings;
use TYPO3\CMS\Core\TypoScript\TemplateService;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\RootlineUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;

class SettingsUtility
{
    protected array $settings = [];

    public function __construct(?ServerRequestInterface $request = null)
    {
        $this->loadSettings($request);
    }

    protected function getRequest(): ?ServerRequestInterface
    {
        return $GLOBALS['TYPO3_REQUEST'] ?? null;
    }

    protected function loadSettings(?ServerRequestInterface $request = null): void
    {
        $version = VersionNumberUtility::getCurrentTypo3Version();

        try {
            // Fallback for TYPO3 12
            if (version_compare($version, '12.4.0', '>=') && version_compare($version, '13.0.0', '<')) {

                $pluginConfiguration = GeneralUtility::makeInstance(ConfigurationManager::class)
                    ->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT)['plugin.']['tx_pictureino.'] ?? null;

                // @see https://buergel.dev/blog/post/typo3-middleware-typoscript-konfiguration
                if (null === $pluginConfiguration && $rootPage = ($request ?? $this->getRequest())?->getAttribute('site')?->getRootPageId()) {
                    $rootlineUtility = GeneralUtility::makeInstance(RootlineUtility::class, $rootPage);

                    // @phpstan-ignore-next-line
                    $templateService = GeneralUtility::makeInstance(TemplateService::class);
                    // @phpstan-ignore-next-line
                    $templateService->tt_track = 0;
                    // @phpstan-ignore-next-line
                    $templateService->runThroughTemplates($rootlineUtility->get());
                    // @phpstan-ignore-next-line
                    $templateService->generateConfig();
                    // @phpstan-ignore-next-line
                    $pluginConfiguration = $templateService->setup['plugin.']['tx_pictureino.'] ?? null;
                }

                $this->settings = GeneralUtility::makeInstance(TypoScriptService::class)->convertTypoScriptArrayToPlainArray($pluginConfiguration ?? []);
            } else {
                $siteSettings = ($request ?? $this->getRequest())?->getAttribute('site')?->getSettings('zeroseven/pictureino');

                if ($siteSettings instanceof SiteSettings) {
                    $this->settings = $siteSettings->get('pictureino') ?? [];
                }
            }
        } catch (\Exception $e) {
            $this->settings = [];
        }
    }

    public function getSettings(): array
    {
        return $this->settings;
    }

    public function get(string $key): mixed
    {
        return $this->settings[$key] ?? null;
    }
}
