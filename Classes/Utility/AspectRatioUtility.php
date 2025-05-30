<?php

declare(strict_types=1);

namespace Zeroseven\Pictureino\Utility;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use Zeroseven\Pictureino\Entity\AspectRatio;

class AspectRatioUtility
{
    protected array $aspectRatios = [];
    protected array $breakpointMap = [];

    public function __construct()
    {
        $this->aspectRatios = [0 => null];
        $this->breakpointMap = GeneralUtility::makeInstance(SettingsUtility::class)->getBreakpoints();
    }

    /** @throws \Exception */
    protected function mapBreakpoint(mixed $view): int
    {
        if (MathUtility::canBeInterpretedAsInteger($view)) {
            return (int) $view;
        }

        if (is_string($view) && isset($this->breakpointMap[$view])) {
            return $this->breakpointMap[$view];
        }

        throw new \Exception('Invalid breakpoint: "' . $view . '". Must be an integer or one of the registered strings (' . implode(', ', array_map(static fn ($s) => '"' . $s . '"', array_keys($this->breakpointMap))) . ')');
    }

    public function sortAspectRatios(): self
    {
        if (count($this->aspectRatios) > 1) {
            ksort($this->aspectRatios);
        }

        $lastAspectRatio = '';
        foreach ($this->aspectRatios as $breakpoint => $aspectRatio) {
            if ($lastAspectRatio === (string) $aspectRatio) {
                unset($this->aspectRatios[$breakpoint]);
            }

            $lastAspectRatio = (string) $aspectRatio;
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

    public function getAspectForWidth(int $width): ?AspectRatio
    {
        if (1 === count($this->aspectRatios)) {
            return $this->getFirstAspectRatio();
        }

        $lastAspectRatio = null;
        foreach ($this->aspectRatios as $breakpoint => $aspectRatio) {
            if ($breakpoint > $width) {
                return $lastAspectRatio;
            }
            $lastAspectRatio = $aspectRatio;
        }

        return $lastAspectRatio;
    }

    /** @throws \Exception */
    public function addList(mixed $input): self
    {
        if (is_array($input) && count($input) > 0) {
            foreach ($input as $view => $ratio) {
                $this->add($ratio, $view, false);
            }

            return $this->sortAspectRatios();
        }

        if (is_string($input) && str_starts_with($input, '{') && ($decoded = json_decode($input, true)) !== null) {
            return $this->addList($decoded);
        }

        return $this->add($input, 0);
    }

    /** @throws \Exception */
    public function add(mixed $asepectRatio, mixed $view = null, bool $sortAspectRatios = true): self
    {
        if (!empty($asepectRatio)) {
            $breakpoint = $this->mapBreakpoint($view ?? 0);

            $this->aspectRatios[$breakpoint] = GeneralUtility::makeInstance(AspectRatio::class)->set($asepectRatio);
        }

        return false === $sortAspectRatios ? $this : $this->sortAspectRatios();
    }

    public function isEmpty(): bool
    {
        return empty($this->aspectRatios[0]) && count($this->aspectRatios) <= 1;
    }

    public function count(): int
    {
        return count($this->aspectRatios);
    }

    public function toArray(): array
    {
        $result = [];

        foreach ($this->aspectRatios as $breakpoint => $aspectRatio) {
            $result[$breakpoint] = $aspectRatio ? $aspectRatio->toArray() : null;
        }

        return $result;
    }
}
