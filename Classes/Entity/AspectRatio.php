<?php declare(strict_types=1);

namespace Zeroseven\Picturerino\Entity;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

class AspectRatio {
    protected int $x = 1;
    protected int $y = 1;

    public function __construct(int $x = null, int $y = null)
    {
        $x && $y && $this->setX($x)->setY($y)->reduce();
    }

    public static function isValidString(string $aspectRatio): bool
    {
        return (bool)preg_match('/^\d+:\d+$/', $aspectRatio);
    }

    public static function splitString(string $aspectRatio): ?array
    {
        if (self::isValidString($aspectRatio)) {
            return array_slice(GeneralUtility::intExplode(':', $aspectRatio, false), 0, 2);
        }

        return null;
    }

    public function getX(): int {
        return $this->x;
    }

    public function setX(int $x): self
    {
        $this->x = $x;

        return $this;
    }

    public function getY(): int {
        return $this->y;
    }

    public function setY(int $y): self
    {
        $this->y = $y;

        return $this;
    }

    public function reduce(): self
    {
        $gcd = function($a, $b) use (&$gcd) {
            return $b === 0 ? $a : $gcd($b, $a % $b);
        };

        $gcdValue = $gcd($this->x, $this->y);

        return $this->setX($this->x / $gcdValue)->setY($this->y / $gcdValue);
    }

    public function set(...$input): self
    {
        if (count($input) === 1 && is_string($input[0]) && $aspectRatio = self::splitString($input[0])) {
            return $this->setX($aspectRatio[0])->setY($aspectRatio[1])->reduce();
        }

        if (count($input) === 2 && MathUtility::canBeInterpretedAsInteger($input[0]) && MathUtility::canBeInterpretedAsInteger($input[1])) {
            return $this->setX((int)$input[0])->setY((int)$input[1])->reduce();
        }

        throw new \InvalidArgumentException('Use arguments like "set(\'16:9\')" or "set(\'400, 300\')"', 1382284106);
    }

    public function getHeight(int $width): int
    {
        return (int)floor($width / $this->getX() * $this->getY());
    }

    public function getWidth(int $height): int
    {
        return (int)floor($height / $this->getY() * $this->getX());
    }

    public function __tostring(): string
    {
        return $this->x . ':' . $this->y;
    }
}
