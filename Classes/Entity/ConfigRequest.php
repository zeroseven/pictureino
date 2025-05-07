<?php

declare(strict_types=1);

namespace Zeroseven\Pictureino\Entity;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Zeroseven\Pictureino\Utility\EncryptionUtility;

class ConfigRequest
{
    protected ?array $config = null;
    protected ?int $width = null;
    protected ?int $height = null;
    protected ?int $viewport = null;
    protected bool $retina = false;
    protected bool $webpSupport = false;

    public static function parseRequest(ServerRequestInterface $request): self
    {
        $configRequest = GeneralUtility::makeInstance(self::class);
        $path = $request->getUri()->getPath();

        if (
            // Make an simple test first ...
            str_starts_with($path, '/-/pictureino/img/')

            // Path like "/-/img/10242xcHVvWmMzWDVERzFnVkRQSW/webp/200x100/" to "1024" (viewport), "2" (retina = 2x), "cHVvWmMzWDVERzFnVkRQSW==" (the config), "webp" (webP Support), "200" (width), and "100" (height),
            && preg_match('/\/-\/pictureino\/img\/(\d+)([12])x([A-Za-z0-9+=]+)\/(?:(webp)\/)?(\d+)x(\d+)\/?$/', $path, $matches)

            // Check if the config is valid
            && $config = EncryptionUtility::decryptConfig($matches[3])
        ) {
            $configRequest->setConfig($config)
                ->setWidth((int) $matches[5])
                ->setHeight((int) $matches[6])
                ->setViewport((int) $matches[1])
                ->setRetina(2 === (int) $matches[2])
                ->setWebpSupport('webp' === (string) $matches[4]);
        }

        return $configRequest;
    }

    public function getConfig(): ?array
    {
        return $this->config;
    }

    public function setConfig(array $config): self
    {
        $this->config = $config;

        return $this;
    }

    public function addConfig(string $key, mixed $value): self
    {
        $this->config[$key] = $value;

        return $this;
    }

    public function encryptConfig(): string
    {
        return EncryptionUtility::encryptConfig($this->config);
    }

    public function getWidth(): ?int
    {
        return $this->width;
    }

    public function setWidth(int $width): self
    {
        $this->width = $width;

        return $this;
    }

    public function getHeight(): ?int
    {
        return $this->height;
    }

    public function setHeight(int $height): self
    {
        $this->height = $height;

        return $this;
    }

    public function getViewport(): ?int
    {
        return $this->viewport;
    }

    public function setViewport(int $viewport): self
    {
        $this->viewport = $viewport;

        return $this;
    }

    public function isRetina(): bool
    {
        return $this->retina;
    }

    public function setRetina(bool $retina): self
    {
        $this->retina = $retina;

        return $this;
    }

    public function hasWebpSupport(): bool
    {
        return $this->webpSupport;
    }

    public function setWebpSupport(bool $webpSupport): self
    {
        $this->webpSupport = $webpSupport;

        return $this;
    }

    public function isValid(): bool
    {
        return null !== $this->width
            && null !== $this->height
            && null !== $this->viewport
            && null !== $this->config;
    }

    public function toArray(): array
    {
        return [
            'config' => $this->config,
            'width' => $this->width,
            'height' => $this->height,
            'viewport' => $this->viewport,
            'retina' => $this->retina,
            'webpSupport' => $this->webpSupport,
        ];
    }
}
