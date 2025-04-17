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
use Zeroseven\Picturerino\Entity\ConfigRequest;
use Zeroseven\Picturerino\Utility\AspectRatioUtility;
use Zeroseven\Picturerino\Utility\ImageUtility;
use Zeroseven\Picturerino\Utility\MetricsUtility;

class Image implements MiddlewareInterface
{
    protected ?ConfigRequest $configRequest = null;
    protected ?ImageUtility $imageUtiltiy = null;
    protected ?AspectRatioUtility $aspectRatioUtiltiy = null;
    protected ?MetricsUtility $metricsUtility = null;
    protected ?AspectRatio $aspectRatio = null;

    protected function initializeConfig(ServerRequestInterface $request): bool
    {
        $this->configRequest = GeneralUtility::makeInstance(ConfigRequest::class, $request);

        if ($this->configRequest->isValid()) {
            $config = $this->configRequest->getConfig();

            $this->imageUtiltiy = GeneralUtility::makeInstance(ImageUtility::class)->setFile(
                (string)($config['file']['src'] ?? ''),
                $config['file']['image'] ?? null,
                (bool)($config['file']['treatIdAsReference'] ?? false)
            );

            $this->aspectRatio = GeneralUtility::makeInstance(AspectRatioUtility::class)
                    ->setAspectRatios($config['aspectRatio'] ?? null)
                    ->getAspectForWidth($this->configRequest->getViewport());

            $this->metricsUtility = GeneralUtility::makeInstance(MetricsUtility::class, $this->configRequest, $this->imageUtiltiy, $this->aspectRatio);
            $this->metricsUtility->log();

            return true;
        }

        return false;
    }

    public function getAttributes(): array
    {
        $this->imageUtiltiy->processImage($this->metricsUtility->getWidth(), $this->metricsUtility->getHeight());

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
                    'attributes' => $this->getAttributes(),
                    'aspectRatio' => $this->aspectRatio->toArray(),
                    'request' => $this->configRequest->toArray(),
                ],
                200,
                ['cache-control' => 'no-store, no-cache, must-revalidate, max-age=0'],
            );
        }

        return $handler->handle($request);
    }
}
