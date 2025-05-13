<?php

declare(strict_types=1);

namespace Zeroseven\Pictureino\Utility;

use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteSettings;
use TYPO3\CMS\Core\TypoScript\TemplateService;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\RootlineUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;

/**
 * @method array getBreakpoints()
 * @method int getMaxImageDimensions()
 * @method int isRetina()
 * @method int isDebug()
 */
class SettingsUtility
{
    protected array $settings = [];

    public function __construct(?Site $site = null)
    {
        $this->loadSettings($site);
    }

    protected function getSite(): ?Site
    {
        return $GLOBALS['TYPO3_REQUEST']?->getAttribute('site');
    }

    protected function sanitizeBreakpointSettings(): void
    {
        $breakpoints = [];

        foreach ($this->get('breakpoints') ?? [] as $key => $value) {
            if (is_string($key) && preg_match('/^\d+\:\d+$/', $value)) {
                $breakpoints[$key] = $value;
                break;
            }

            if (is_int($key) && preg_match('/(.+)\s*:\s*(\d+)/', $value, $matches)) {
                $breakpoints[$matches[1]] = (int) $matches[2];
            }
        }

        asort($breakpoints);

        // Add first breakpoint
        if (0 !== reset($breakpoints)) {
            $breakpoints = ['_default' => 0] + $breakpoints;
        }

        $this->settings['breakpoints'] = $breakpoints;
    }

    protected function loadSettings(?Site $site = null): void
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
                $siteSettings = ($site ?? $this->getSite())?->getSettings();

                if ($siteSettings instanceof SiteSettings) {
                    $this->settings = $siteSettings->get('pictureino') ?? [];
                }
            }
        } catch (\Exception $e) {
            $this->settings = [];
        }

        $this->sanitizeBreakpointSettings();
    }

    public function getSettings(): array
    {
        return $this->settings;
    }

    public function get(string $key): mixed
    {
        return $this->settings[$key] ?? null;
    }

    public function __call($method, $args): mixed
    {
        if (str_starts_with($method, 'get')) {
            $key = lcfirst(substr($method, 3));

            return $this->get($key);
        }

        if (str_starts_with($method, 'is')) {
            $key = lcfirst(substr($method, 2));

            return (bool) $this->get($key);
        }

        throw new \BadMethodCallException("Method {$method} does not exist.");
    }
}
