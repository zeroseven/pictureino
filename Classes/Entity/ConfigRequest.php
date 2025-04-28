<?php

declare(strict_types=1);

namespace Zeroseven\Picturerino\Entity;

use Psr\Http\Message\ServerRequestInterface;
use Zeroseven\Picturerino\Utility\EncryptionUtility;

class ConfigRequest
{
    protected ?array $config = null;
    protected ?int $width = null;
    protected ?int $height = null;
    protected ?string $webpSupport = null;
    protected ?int $viewport = null;
    protected ?bool $retina = null;

    public function __construct(ServerRequestInterface $request)
    {
        $this->parseRequest($request->getUri()->getPath());
    }

    protected function parseRequest(string $path): void
    {
        if (
            // Make an simple test first ...
            str_starts_with($path, '/-/img/')

            // Path like "/-/img/200x100/LyAa10242xcHVvWmMzWDVERzFnVkRQSW==" to "200" (width), "100" (height), "LyAa" (webP Support) "1024" (viewport), "2" (retina = 2x) and "cHVvWmMzWDVERzFnVkRQSW==" (the config)
            && preg_match('/\/-\/img\/(\d+)x(\d+)\/((?:[A-Z][a-z])+)?(\d+)([12])x([A-Za-z0-9+=]+)\/?$/', $path, $matches)

            // Check if the config is valid
            && $this->config = EncryptionUtility::decryptConfig($matches[6])
        ) {
            $this->width = (int)$matches[1];
            $this->height = (int)$matches[2];
            $this->webpSupport = (string)$matches[3];
            $this->viewport = (int)$matches[4];
            $this->retina = 2 === (int)$matches[5];

        }
    }

    public function getConfig(): ?array
    {
        return $this->config;
    }

    public function getWidth(): ?int
    {
        return $this->width;
    }

    public function getHeight(): ?int
    {
        return $this->height;
    }

    public function getWebpSupport(): ?array
    {
        return $this->webpSupport
            ? preg_split('/(?=[A-Z])/', $this->webpSupport, -1, PREG_SPLIT_NO_EMPTY)
            : null;
    }

    public function getViewport(): ?int
    {
        return $this->viewport;
    }

    public function isRetina(): bool
    {
        return $this->retina;
    }

    public function isValid(): bool
    {
        return $this->width !== null
            && $this->height !== null
            && $this->viewport !== null
            && $this->config !== null;
    }

    public function toArray(): array
    {
        return [
            'config' => $this->config,
            'width' => $this->width,
            'height' => $this->height,
            'viewport' => $this->viewport,
        ];
    }
}
