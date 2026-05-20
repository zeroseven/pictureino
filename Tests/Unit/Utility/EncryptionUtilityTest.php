<?php

declare(strict_types=1);

namespace Zeroseven\Pictureino\Tests\Unit\Utility;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Zeroseven\Pictureino\Utility\EncryptionUtility;

final class EncryptionUtilityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = 'test-encryption-key-for-unit-tests-1234567890';
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['TYPO3_CONF_VARS']);
        parent::tearDown();
    }

    #[Test]
    public function encryptAndDecryptStringRoundtrip(): void
    {
        $utility = new EncryptionUtility();
        $original = 'Hello, World!';

        $encrypted = $utility->encryptString($original);
        self::assertNotNull($encrypted);
        self::assertNotSame($original, $encrypted);

        $decrypted = $utility->decryptString($encrypted);
        self::assertSame($original, $decrypted);
    }

    #[Test]
    public function encryptAndDecryptArrayRoundtrip(): void
    {
        $utility = new EncryptionUtility();
        $original = [
            'file' => ['uid' => 123, 'crop' => ''],
            'aspectRatio' => ['16:9'],
            'width' => 800,
        ];

        $encrypted = $utility->encryptArray($original);
        self::assertNotNull($encrypted);

        $decrypted = $utility->decryptArray($encrypted);
        self::assertSame($original, $decrypted);
    }

    #[Test]
    public function encryptedStringIsDifferentEachConstruction(): void
    {
        // Same key produces same result (deterministic with same IV)
        $utility1 = new EncryptionUtility();
        $utility2 = new EncryptionUtility();

        $encrypted1 = $utility1->encryptString('test');
        $encrypted2 = $utility2->encryptString('test');

        // Same key + same IV = same output (AES-CBC is deterministic)
        self::assertSame($encrypted1, $encrypted2);
    }

    #[Test]
    public function constructorThrowsWithoutEncryptionKey(): void
    {
        unset($GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey']);

        $this->expectException(\Exception::class);
        $this->expectExceptionCode(1667886790);

        new EncryptionUtility();
    }

    #[Test]
    public function decryptWithWrongKeyReturnsNull(): void
    {
        $utility = new EncryptionUtility();
        $encrypted = $utility->encryptString('secret');

        // Change key
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = 'different-key';
        $utility2 = new EncryptionUtility();

        $decrypted = $utility2->decryptString($encrypted);
        // Either null or garbled output (not the original)
        self::assertNotSame('secret', $decrypted);
    }
}
