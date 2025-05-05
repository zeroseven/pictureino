<?php

declare(strict_types=1);

namespace Zeroseven\Pictureino\Utility;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Site\Entity\SiteSettings;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
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
        try {

            $version = VersionNumberUtility::getCurrentTypo3Version();
            if (version_compare($version, '12.0.0', '>=') && version_compare($version, '13.0.0', '<')) {

                $pluginConfiguration = GeneralUtility::makeInstance(ConfigurationManager::class)
                            ->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT)['plugin.']['tx_pictureino.'] ?? [];

                $typoScriptService = GeneralUtility::makeInstance(TypoScriptService::class);
                $this->settings = $typoScriptService->convertTypoScriptArrayToPlainArray($pluginConfiguration ?? []);
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
