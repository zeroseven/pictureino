<?php

declare(strict_types=1);

namespace Zeroseven\Pictureino\Tests\Unit\Entity;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Zeroseven\Pictureino\Entity\AspectRatio;

final class AspectRatioTest extends TestCase
{
    #[Test]
    public function constructorReducesRatio(): void
    {
        $ratio = new AspectRatio(1920, 1080);
        self::assertSame(16, $ratio->getX());
        self::assertSame(9, $ratio->getY());
    }

    #[Test]
    public function constructorWithNullKeepsUnset(): void
    {
        $ratio = new AspectRatio();
        self::assertSame('', (string) $ratio);
    }

    public static function validStringProvider(): array
    {
        return [
            '16:9' => ['16:9', true],
            '4:3' => ['4:3', true],
            '1:1' => ['1:1', true],
            'invalid text' => ['foo', false],
            'missing colon' => ['169', false],
            'with spaces' => ['16 : 9', false],
            'decimal' => ['1.5:1', false],
        ];
    }

    #[Test]
    #[DataProvider('validStringProvider')]
    public function isValidStringValidatesCorrectly(string $input, bool $expected): void
    {
        self::assertSame($expected, AspectRatio::isValidString($input));
    }

    #[Test]
    public function splitStringReturnsArrayForValidInput(): void
    {
        $result = AspectRatio::splitString('16:9');
        self::assertSame([16, 9], $result);
    }

    #[Test]
    public function splitStringReturnsNullForInvalidInput(): void
    {
        self::assertNull(AspectRatio::splitString('invalid'));
    }

    #[Test]
    public function setWithStringParsesAndReduces(): void
    {
        $ratio = new AspectRatio();
        $ratio->set('1920:1080');
        self::assertSame(16, $ratio->getX());
        self::assertSame(9, $ratio->getY());
    }

    #[Test]
    public function setWithArrayParsesAndReduces(): void
    {
        $ratio = new AspectRatio();
        $ratio->set([400, 300]);
        self::assertSame(4, $ratio->getX());
        self::assertSame(3, $ratio->getY());
    }

    #[Test]
    public function setWithNullClearsValues(): void
    {
        $ratio = new AspectRatio(16, 9);
        $ratio->set(null);
        self::assertSame('', (string) $ratio);
    }

    #[Test]
    public function setWithEmptyStringClearsValues(): void
    {
        $ratio = new AspectRatio(16, 9);
        $ratio->set('');
        self::assertSame('', (string) $ratio);
    }

    #[Test]
    public function setWithInvalidArgumentThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(1382284106);

        $ratio = new AspectRatio();
        $ratio->set('invalid');
    }

    #[Test]
    public function getHeightCalculatesCorrectly(): void
    {
        $ratio = new AspectRatio(16, 9);
        // 1920 / 16 * 9 = 1080
        self::assertSame(1080, $ratio->getHeight(1920));
    }

    #[Test]
    public function getWidthCalculatesCorrectly(): void
    {
        $ratio = new AspectRatio(16, 9);
        // 1080 / 9 * 16 = 1920
        self::assertSame(1920, $ratio->getWidth(1080));
    }

    #[Test]
    public function toArrayReturnsXAndY(): void
    {
        $ratio = new AspectRatio(16, 9);
        self::assertSame([16, 9], $ratio->toArray());
    }

    #[Test]
    public function toStringReturnsFormattedRatio(): void
    {
        $ratio = new AspectRatio(16, 9);
        self::assertSame('16:9', (string) $ratio);
    }

    #[Test]
    public function reduceSimplifiesRatio(): void
    {
        $ratio = new AspectRatio();
        $ratio->setX(100)->setY(50)->reduce();
        self::assertSame(2, $ratio->getX());
        self::assertSame(1, $ratio->getY());
    }
}
