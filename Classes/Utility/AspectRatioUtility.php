<?php declare(strict_types=1);

namespace Zeroseven\Picturerino\Utility;

use Exception;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use Zeroseven\Picturerino\Entity\AspectRatio;


class AspectRatioUtility {
    protected array $aspectRatio;
    protected array $breakpointMap;

    public function __construct() {
        $this->aspectRatio = [0 => null];
        $this->breakpointMap = [
            'xs' => 0,
            'sm' => 576,
            'md' => 768,
            'lg' => 992,
            'xl' => 1200,
            'xxl' => 1400
        ];
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

    public function getAspectRatios(): array
    {
        return $this->aspectRatio;
    }

    public function getFirstAspectRatio(): ?AspectRatio
    {
        return $this->aspectRatio[0] ?? null;
    }

    /** @throws Exception */
    public function setAspectRatios(mixed $input): self
    {
        if (is_array($input) && count($input) > 0) {
            foreach ($input as $view => $ratio) {
                $this->addAspectRatio($ratio, $view);
            }

            return $this;
        }

        $this->addAspectRatio($input, 0);

        return $this;
    }

    /** @throws Exception */
    public function addAspectRatio(mixed $asepectRatio, mixed $view = null): self
    {
        if (!empty($asepectRatio)) {
            $breakpoint =  $this->mapBreakpoint($view ?? 0);

            $this->aspectRatio[$breakpoint] = GeneralUtility::makeInstance(AspectRatio::class)->set($asepectRatio);

            if (count($this->aspectRatio) > 1) {
                ksort($this->aspectRatio);
            }
        }

        return $this;
    }

    /** @throws Exception */
    public function removeAspectRatio(mixed $view): self
    {
        $breakpoint = $this->mapBreakpoint($view);

        if ($breakpoint === 0) {
            $this->aspectRatio[0] = null;

            return $this;
        }

        if (isset($this->aspectRatio[$breakpoint])) {
            unset($this->aspectRatio[$breakpoint]);
        }

        return $this;
    }

    public function isEmpty(): bool
    {
        return count($this->aspectRatio) <= 1 && $this->aspectRatio[0] === null;
    }
}
