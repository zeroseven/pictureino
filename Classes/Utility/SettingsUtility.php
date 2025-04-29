<?php

declare(strict_types=1);

namespace Zeroseven\Pictureino\Utility;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Site\Entity\SiteSettings;

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
            $siteSettings = ($request ?? $this->getRequest())?->getAttribute('site')?->getSettings('zeroseven/pictureino');

            if ($siteSettings instanceof SiteSettings) {
                $this->settings = $siteSettings->get('pictureino') ?? [];
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
