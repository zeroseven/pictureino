<?php

declare(strict_types=1);

namespace Zeroseven\Picturerino\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Zeroseven\Picturerino\Entity\AspectRatio;
use Zeroseven\Picturerino\Utility\AspectRatioUtility;
use Zeroseven\Picturerino\Utility\EncryptionUtility;
use Zeroseven\Picturerino\Utility\ImageUtility;

class Image implements MiddlewareInterface
{
    protected ?ImageUtility $imageUtiltiy = null;
    protected ?AspectRatioUtility $aspectRatioUtiltiy = null;
    protected ?int $width = null;
    protected ?int $height = null;
    protected ?AspectRatio $aspectRatio = null;

    protected function initializeConfig(ServerRequestInterface $request): bool
    {
        $path = $request->getUri()->getPath();

        if (
            // Path like "/-/img/200x100/1024/cHVvWmMzWDVERzFnVkRQSW==" to "200", "100" and "cHVvWmMzWDVERzFnVkRQSW=="
            preg_match('/\/-\/img\/(\d+)x(\d+)\/(\d+)\/([A-Za-z0-9+=]+)\/?$/', $path, $matches)
            && $config = EncryptionUtility::decryptConfig($matches[4])
        ) {
            $this->imageUtiltiy = GeneralUtility::makeInstance(ImageUtility::class)->setFile(
                (string)($config['file']['src'] ?? ''),
                $config['file']['image'] ?? null,
                (bool)($config['file']['treatIdAsReference'] ?? false)
            );

            $this->width = (int)$matches[1];
            $this->height = (int)$matches[2];

            $this->aspectRatio = GeneralUtility::makeInstance(AspectRatioUtility::class)
                ->setAspectRatios($config['aspectRatio'] ?? null)
                ->getAspectForWidth((int)$matches[3]);

            return true;
        }

        return false;
    }

    public function processFile(): array
    {
        $this->imageUtiltiy->processImage($this->width, $this->aspectRatio?->getHeight($this->width));

        return [
            'src' => $this->imageUtiltiy->getUrl(),
            'width' => $this->imageUtiltiy->getProperty('width'),
            'height' => $this->imageUtiltiy->getProperty('height')
        ];
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->initializeConfig($request)) {
            return new JsonResponse([
                    'attributes' => $this->processFile(),
                    'aspectRatio' => $this->aspectRatio->toArray(),
                    'request' => [
                        'width' => $this->width,
                        'height' => $this->height
                    ]
                ],
                200,
                ['cache-control' => 'no-store, no-cache, must-revalidate, max-age=0'],
            );
        }

        return $handler->handle($request);
    }
}
