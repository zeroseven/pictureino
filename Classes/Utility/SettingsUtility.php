<?php declare(strict_types=1);

namespace Zeroseven\Picturerino\Utility;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Site\Entity\SiteSettings;

class SettingsUtility
{
    protected array $settings = [];

    public function __construct()
    {
        $this->loadSettings();
    }

    protected function getRequest(): ?ServerRequestInterface
    {
        return $GLOBALS['TYPO3_REQUEST'] ?? null;
    }

    protected function loadSettings(): void
    {
        try {
            $siteSettings = $this->getRequest()?->getAttribute('site')?->getSettings('zeroseven/picturerino');

            if ($siteSettings instanceof SiteSettings) {
                $this->settings = $siteSettings->get('picturerino') ?? [];
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
