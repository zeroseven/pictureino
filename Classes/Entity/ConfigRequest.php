<?php declare(strict_types=1);

namespace Zeroseven\Picturerino\Entity;

use Psr\Http\Message\ServerRequestInterface;
use Zeroseven\Picturerino\Utility\EncryptionUtility;

class ConfigRequest
{
    protected ?array $config = null;
    protected ?int $width = null;
    protected ?int $height = null;
    protected ?int $viewport = null;
    protected bool $isValid = false;

    public function __construct(ServerRequestInterface $request)
    {
        $this->parseRequest($request->getUri()->getPath());
    }

    protected function parseRequest(string $path): void
    {
        if (
            // Path like "/-/img/200x100/1024/cHVvWmMzWDVERzFnVkRQSW==" to "200", "100" and "cHVvWmMzWDVERzFnVkRQSW=="
            preg_match('/\/-\/img\/(\d+)x(\d+)\/(\d+)\/([A-Za-z0-9+=]+)\/?$/', $path, $matches)
            && $this->config = EncryptionUtility::decryptConfig($matches[4])
        ) {
            $this->width = (int)$matches[1];
            $this->height = (int)$matches[2];
            $this->viewport = (int)$matches[3];
            $this->isValid = true;
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

    public function isValid(): bool
    {
        return $this->isValid;
    }

    public function toArray(): array
    {
        return [
            'config' => $this->config,
            'width' => $this->width,
            'height' => $this->height,
            'viewport' => $this->viewport
        ];
    }
}
