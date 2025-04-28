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
    protected ?int $viewport = null;
    protected bool $retina = false;
    protected bool $webpSupport = false;

    public function __construct(ServerRequestInterface $request)
    {
        $this->parseRequest($request->getUri()->getPath());
    }

    protected function parseRequest(string $path): void
    {
        if (
            // Make an simple test first ...
            str_starts_with($path, '/-/picturerino/img/')

            // Path like "/-/img/10242xcHVvWmMzWDVERzFnVkRQSW/webp/200x100/" to "1024" (viewport), "2" (retina = 2x), "cHVvWmMzWDVERzFnVkRQSW==" (the config), "webp" (webP Support), "200" (width), and "100" (height),
            && preg_match('/\/-\/picturerino\/img\/(\d+)([12])x([A-Za-z0-9+=]+)\/(?:(webp)\/)?(\d+)x(\d+)\/?$/', $path, $matches)

            // Check if the config is valid
            && $this->config = EncryptionUtility::decryptConfig($matches[3])
        ) {
            $this->width = (int)$matches[5];
            $this->height = (int)$matches[6];
            $this->viewport = (int)$matches[1];
            $this->retina = 2 === (int)$matches[2];
            $this->webpSupport = 'webp' === (string)$matches[4];

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

    public function getViewport(): ?int
    {
        return $this->viewport;
    }

    public function isRetina(): bool
    {
        return $this->retina;
    }

    public function hasWebpSupport(): bool
    {
        return $this->webpSupport;
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
