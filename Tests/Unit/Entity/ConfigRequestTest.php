<?php

declare(strict_types=1);

namespace Zeroseven\Pictureino\Tests\Unit\Entity;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Zeroseven\Pictureino\Entity\ConfigRequest;

final class ConfigRequestTest extends TestCase
{
    #[Test]
    public function settersAndGettersWork(): void
    {
        $request = new ConfigRequest();
        $request->setWidth(800)
            ->setHeight(600)
            ->setViewport(1024)
            ->setRetina(true)
            ->setWebpSupport(true)
            ->setConfig(['file' => ['uid' => 1]]);

        self::assertSame(800, $request->getWidth());
        self::assertSame(600, $request->getHeight());
        self::assertSame(1024, $request->getViewport());
        self::assertTrue($request->isRetina());
        self::assertTrue($request->hasWebpSupport());
        self::assertSame(['file' => ['uid' => 1]], $request->getConfig());
    }

    #[Test]
    public function addConfigAppendsToExistingConfig(): void
    {
        $request = new ConfigRequest();
        $request->setConfig(['file' => ['uid' => 1]]);
        $request->addConfig('aspectRatio', ['16:9']);

        self::assertSame([
            'file' => ['uid' => 1],
            'aspectRatio' => ['16:9'],
        ], $request->getConfig());
    }

    #[Test]
    public function isValidReturnsTrueWhenAllFieldsSet(): void
    {
        $request = new ConfigRequest();
        $request->setWidth(800)
            ->setHeight(600)
            ->setViewport(1024)
            ->setConfig(['file' => ['uid' => 1]]);

        self::assertTrue($request->isValid());
    }

    #[Test]
    public function isValidReturnsFalseWhenFieldsMissing(): void
    {
        $request = new ConfigRequest();
        self::assertFalse($request->isValid());

        $request->setWidth(800);
        self::assertFalse($request->isValid());

        $request->setHeight(600);
        self::assertFalse($request->isValid());

        $request->setViewport(1024);
        self::assertFalse($request->isValid());
    }

    #[Test]
    public function toArrayContainsAllFields(): void
    {
        $request = new ConfigRequest();
        $request->setWidth(800)
            ->setHeight(600)
            ->setViewport(1024)
            ->setRetina(true)
            ->setWebpSupport(false)
            ->setConfig(['test' => 'value']);

        self::assertSame([
            'config' => ['test' => 'value'],
            'width' => 800,
            'height' => 600,
            'viewport' => 1024,
            'retina' => true,
            'webpSupport' => false,
        ], $request->toArray());
    }

    #[Test]
    public function parseRequestMatchesValidPath(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = 'test-key-for-unit-tests-1234567890';

        // Encrypt a config to build a valid URL
        $utility = new \Zeroseven\Pictureino\Utility\EncryptionUtility();
        $config = ['file' => ['uid' => 42]];
        $encrypted = $utility->encryptArray($config);

        $path = '/-/pictureino/img/10242x' . $encrypted . '/webp/800x600/';

        $uri = $this->createMock(UriInterface::class);
        $uri->method('getPath')->willReturn($path);

        $serverRequest = $this->createMock(ServerRequestInterface::class);
        $serverRequest->method('getUri')->willReturn($uri);

        $result = ConfigRequest::parseRequest($serverRequest);

        self::assertTrue($result->isValid());
        self::assertSame(800, $result->getWidth());
        self::assertSame(600, $result->getHeight());
        self::assertSame(1024, $result->getViewport());
        self::assertTrue($result->isRetina());
        self::assertTrue($result->hasWebpSupport());
        self::assertSame($config, $result->getConfig());

        unset($GLOBALS['TYPO3_CONF_VARS']);
    }

    #[Test]
    public function parseRequestReturnsInvalidForNonMatchingPath(): void
    {
        $uri = $this->createMock(UriInterface::class);
        $uri->method('getPath')->willReturn('/some/other/path');

        $serverRequest = $this->createMock(ServerRequestInterface::class);
        $serverRequest->method('getUri')->willReturn($uri);

        $result = ConfigRequest::parseRequest($serverRequest);
        self::assertFalse($result->isValid());
    }
}
