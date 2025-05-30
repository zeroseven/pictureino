<?php

declare(strict_types=1);

namespace Zeroseven\Pictureino\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Zeroseven\Pictureino\Entity\ConfigRequest;
use Zeroseven\Pictureino\Utility\ImageUtility;
use Zeroseven\Pictureino\Utility\LogUtility;
use Zeroseven\Pictureino\Utility\MetricsUtility;
use Zeroseven\Pictureino\Utility\SettingsUtility;

class ImageRequest implements MiddlewareInterface
{
    protected const REQUEST_HEADERS = [
        'cache-control' => 'no-store, no-cache, must-revalidate, max-age=0',
        'x-robots' => 'noindex, nofollow',
    ];

    protected ?ConfigRequest $configRequest = null;
    protected ?ImageUtility $imageUtiltiy = null;
    protected ?MetricsUtility $metricsUtility = null;
    protected ?LogUtility $logUtility = null;
    protected ?SettingsUtility $settingsUtility = null;

    protected function isRetina(): bool
    {
        $config = $this->configRequest->getConfig()['retina'] ?? null;

        if ($this->configRequest->isRetina() && false !== $config) {
            return $this->settingsUtility->get('retina') || true === $config;
        }

        return false;
    }

    protected function initializeConfig(ServerRequestInterface $request): bool
    {
        $this->configRequest = ConfigRequest::parseRequest($request);

        if ($this->configRequest->isValid() && $config = $this->configRequest->getConfig()) {
            $identifier = md5($request->getAttribute('site')?->getIdentifier() . ($config['pid'] ?? '') . ($config['cropVariant'] ?? '') . json_encode($config['file'] ?? []));

            $this->settingsUtility = GeneralUtility::makeInstance(SettingsUtility::class, $request->getAttribute('site'));

            $this->imageUtiltiy = GeneralUtility::makeInstance(ImageUtility::class)->setFile(
                (string) ($config['file']['src'] ?? ''),
                $config['file']['image'] ?? null,
                (bool) ($config['file']['treatIdAsReference'] ?? false)
            );

            if ($cropVariant = $config['cropVariant'] ?? null) {
                $this->imageUtiltiy->setCropVariant($cropVariant);
            }

            $this->metricsUtility = GeneralUtility::makeInstance(MetricsUtility::class, $identifier, $this->configRequest, $this->imageUtiltiy, $this->settingsUtility);
            $this->logUtility = GeneralUtility::makeInstance(LogUtility::class, $identifier, $this->metricsUtility, $this->configRequest, $this->imageUtiltiy);

            return $this->metricsUtility->validate();
        }

        return false;
    }

    protected function processImage(): array
    {
        $pixelDensity = $this->isRetina() ? 2 : 1;

        $this->imageUtiltiy->processImage($this->metricsUtility->getWidth() * $pixelDensity, $this->metricsUtility->getHeight() * $pixelDensity, $this->configRequest->hasWebpSupport());

        return [
            'img' . $pixelDensity . 'x' => $this->imageUtiltiy->getUrl(),
            'width' => (int) $this->imageUtiltiy->getProperty('width') / $pixelDensity,
            'height' => (int) $this->imageUtiltiy->getProperty('height') / $pixelDensity,
        ];
    }

    protected function returnErrorResponse(string $message, int $code, ?int $status = null): JsonResponse
    {
        return new JsonResponse(['error' => [
            'message' => $message,
            'code' => $code,
        ]], $status ?? 400, static::REQUEST_HEADERS);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            $requestStartTime = microtime(true);

            if ($this->initializeConfig($request)) {
                $data = [
                    'processed' => $this->processImage(),
                    'view' => $this->configRequest->getViewport(),
                ];

                if ($this->settingsUtility->isDebug()) {
                    $data['debug'] = [
                        'request' => $this->configRequest->toArray(),
                        'metrics' => $this->metricsUtility->toArray(),
                        'time' => round((microtime(true) - $requestStartTime) * 1000, 2) . 'ms',
                    ];
                }

                $this->metricsUtility->limitExceeded() || $this->logUtility->log();

                return new JsonResponse($data, 200, static::REQUEST_HEADERS);
            }
        } catch (\InvalidArgumentException $e) {
            return $this->returnErrorResponse($e->getMessage(), $e->getCode());
        }

        return $handler->handle($request);
    }
}
