<?php declare(strict_types=1);

namespace Zeroseven\Picturerino\Utility;

use Exception;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use Zeroseven\Picturerino\Entity\AspectRatio;


class AspectRatioUtility {
    protected SettingsUtility $settingsUtility;
    protected array $aspectRatios;
    protected array $breakpointMap;

    public function __construct() {
        $this->aspectRatios = [0 => null];

        if ($breakpoints = GeneralUtility::makeInstance(SettingsUtility::class)->get('breakpoints')) {
            foreach ($breakpoints as $setup) {
                if (preg_match('/(.+)\s*:\s*(\d+)/', $setup, $matches)) {
                    $this->breakpointMap[$matches[1]] = (int)$matches[2];
                }
            }
        }
    }

    /** @throws Exception */
    protected function mapBreakpoint(mixed $view): int
    {
        if (MathUtility::canBeInterpretedAsInteger($view)) {
            return (int)$view;
        }

        if (is_string($view) && isset($this->breakpointMap[$view])) {
            return $this->breakpointMap[$view];
        }

        throw new Exception('Invalid breakpoint: ' . $view . '. Must be an integer or a string.');
    }

    public function sortAspectRatios(): self
    {
        if (count($this->aspectRatios) > 1) {
            ksort($this->aspectRatios);
        }

        $lastAspectRatio = '';
        foreach ($this->aspectRatios as $breakpoint => $aspectRatio) {
            if ($lastAspectRatio === (string)$aspectRatio) {
                unset($this->aspectRatios[$breakpoint]);
            }

            $lastAspectRatio = (string)$aspectRatio;
        }

        return $this;
    }

    public function getAspectRatios(): array
    {
        return $this->aspectRatios;
    }

    public function getFirstAspectRatio(): ?AspectRatio
    {
        return $this->aspectRatios[0] ?? null;
    }

    /** @throws Exception */
    public function setAspectRatios(mixed $input): self
    {
        if (is_array($input) && count($input) > 0) {
            foreach ($input as $view => $ratio) {
                $this->addAspectRatio($ratio, $view, false);
            }

            return $this->sortAspectRatios();
        }

        $this->addAspectRatio($input, 0);

        return $this;
    }

    /** @throws Exception */
    public function addAspectRatio(mixed $asepectRatio, mixed $view = null, bool $sortAspectRatios = true): self
    {
        if (!empty($asepectRatio)) {
            $breakpoint =  $this->mapBreakpoint($view ?? 0);

            $this->aspectRatios[$breakpoint] = GeneralUtility::makeInstance(AspectRatio::class)->set($asepectRatio);
        }

        return $sortAspectRatios === false ? $this : $this->sortAspectRatios();
    }

    /** @throws Exception */
    public function removeAspectRatio(mixed $view): self
    {
        $breakpoint = $this->mapBreakpoint($view);

        if ($breakpoint === 0) {
            $this->aspectRatios[0] = null;

            return $this;
        }

        if (isset($this->aspectRatios[$breakpoint])) {
            unset($this->aspectRatios[$breakpoint]);
        }

        return $this;
    }

    public function isEmpty(): bool
    {
        return count($this->aspectRatios) <= 1 && $this->aspectRatios[0] === null;
    }

    public function count(): int
    {
        return count($this->aspectRatios);
    }
}
