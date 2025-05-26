<?php

declare(strict_types=1);

namespace Zeroseven\Pictureino\Entity;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

class AspectRatio
{
    protected ?int $x = null;
    protected ?int $y = null;

    public function __construct(?int $x = null, ?int $y = null)
    {
        $x && $y && $this->setX($x)->setY($y)->reduce();
    }

    public static function isValidString(string $aspectRatio): bool
    {
        return (bool) preg_match('/^\d+:\d+$/', $aspectRatio);
    }

    public static function splitString(string $aspectRatio): ?array
    {
        if (self::isValidString($aspectRatio)) {
            return array_slice(GeneralUtility::intExplode(':', $aspectRatio, false), 0, 2);
        }

        return null;
    }

    public function getX(): int
    {
        return $this->x;
    }

    public function setX(?int $x = null): self
    {
        $this->x = $x;

        return $this;
    }

    public function getY(): int
    {
        return $this->y;
    }

    public function setY(?int $y = null): self
    {
        $this->y = $y;

        return $this;
    }

    public function reduce(): self
    {
        $gcd = function ($a, $b) use (&$gcd) {
            return 0 === $b ? $a : $gcd($b, $a % $b);
        };

        $gcdValue = $gcd($this->x, $this->y);

        return $this->setX($this->x / $gcdValue)->setY($this->y / $gcdValue);
    }

    public function set(mixed $value = null): self
    {
        if (is_null($value) || '' === $value) {
            return $this->setX(null)->setY(null);
        }

        if (is_string($value) && $aspectRatio = self::splitString($value)) {
            return $this->setX($aspectRatio[0])->setY($aspectRatio[1])->reduce();
        }

        if (is_array($value) && 2 === count($value) && MathUtility::canBeInterpretedAsInteger($value[0] ?? null) && MathUtility::canBeInterpretedAsInteger($value[1] ?? null)) {
            return $this->setX((int) $value[0])->setY((int) $value[1])->reduce();
        }

        throw new \InvalidArgumentException('Use arguments like "set(\'16:9\')" or "set([400, 300])"', 1382284106);
    }

    public function getHeight(int $width): int
    {
        return (int) floor($width / $this->getX() * $this->getY());
    }

    public function getWidth(int $height): int
    {
        return (int) floor($height / $this->getY() * $this->getX());
    }

    public function toArray(): array
    {
        return [$this->getX(), $this->getY()];
    }

    public function __toString(): string
    {
        if (null === $this->x || null === $this->y) {
            return '';
        }

        return $this->x . ':' . $this->y;
    }
}
