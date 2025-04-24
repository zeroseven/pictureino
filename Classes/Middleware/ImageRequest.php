<?php

declare(strict_types=1);

namespace Zeroseven\Picturerino\Middleware;

use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Zeroseven\Picturerino\Entity\ConfigRequest;
use Zeroseven\Picturerino\Utility\ImageUtility;
use Zeroseven\Picturerino\Utility\LogUtility;
use Zeroseven\Picturerino\Utility\MetricsUtility;
use Zeroseven\Picturerino\Utility\SettingsUtility;

class ImageRequest implements MiddlewareInterface
{
    protected const array REQUEST_HEADERS = [
        'cache-control' => 'no-store, no-cache, must-revalidate, max-age=0',
        'x-robots' => 'noindex, nofollow',
    ];

    protected ?ConfigRequest $configRequest = null;
    protected ?string $identifier = null;
    protected ?ImageUtility $imageUtiltiy = null;
    protected ?MetricsUtility $metricsUtility = null;
    protected ?SettingsUtility $settingsUtility = null;
    protected ?LogUtility $logUtility = null;

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
        $this->configRequest = GeneralUtility::makeInstance(ConfigRequest::class, $request);

        if ($this->configRequest->isValid() && $config = $this->configRequest->getConfig()) {
            $this->identifier = md5($request->getAttribute('site')?->getIdentifier() . json_encode($config['file'] ?? []));
            $this->settingsUtility = GeneralUtility::makeInstance(SettingsUtility::class, $request);

            $this->imageUtiltiy = GeneralUtility::makeInstance(ImageUtility::class)->setFile(
                (string) ($config['file']['src'] ?? ''),
                $config['file']['image'] ?? null,
                (bool) ($config['file']['treatIdAsReference'] ?? false)
            );

            $this->metricsUtility = GeneralUtility::makeInstance(MetricsUtility::class, $this->identifier, $this->configRequest, $this->imageUtiltiy, $this->settingsUtility);
            $this->logUtility = GeneralUtility::makeInstance(LogUtility::class, $this->identifier, $this->configRequest, $this->imageUtiltiy, $this->metricsUtility);

            return $this->metricsUtility->validate();
        }

        return false;
    }

    protected function tooManyRequests(): bool
    {
        if (GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('backend.user', 'isLoggedIn') || $this->logUtility->hasExistingEntry()) {
            return false;
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $currentTime = time();
        $requests = array_filter($_SESSION[$this->identifier] ?? [], fn($timestamp) => $currentTime - $timestamp < 1200);

        if (count($requests) >= 10 * ($this->isRetina() ? 2 : 1)) {
            return true;
        }

        $_SESSION[$this->identifier] = [...$requests, $currentTime];

        return false;
    }

    protected function processImage(): array
    {
        $this->imageUtiltiy->processImage($this->metricsUtility->getWidth(), $this->metricsUtility->getHeight());

        $config = [
            'img' => $this->imageUtiltiy->getUrl(),
            'width' => $this->imageUtiltiy->getProperty('width'),
            'height' => $this->imageUtiltiy->getProperty('height'),
        ];

        if ($this->isRetina()) {
            $this->imageUtiltiy->processImage($this->metricsUtility->getWidth() * 2, $this->metricsUtility->getHeight() * 2);

            $config['img2x'] = $this->imageUtiltiy->getUrl();
        }

        return $config;
    }

    protected function returnErrorResponse(string $message, int $code, int $status = null): JsonResponse
    {
        return new JsonResponse(['error' => [
            'message' => $message,
            'code' => $code,
        ]], $status ?? 400, static::REQUEST_HEADERS);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            if ($this->initializeConfig($request)) {
                if ($this->tooManyRequests()) {
                    return $this->returnErrorResponse('Too many requests', 1310,429);
                }

                $data = [
                    'processed' => $this->processImage(),
                    'view' => $this->configRequest->getViewport(),
                ];

                if ($this->settingsUtility->get('debug')) {
                    $data['debug'] = [
                        'request' => $this->configRequest->toArray(),
                        'metrics' => $this->metricsUtility->toArray(),
                    ];

                    // Override the file config with the identifier
                    $data['debug']['request']['config']['file'] = $this->metricsUtility->getIdentifier();
                }

                $this->logUtility->log();

                return new JsonResponse($data, 200, static::REQUEST_HEADERS);
            }
        } catch (InvalidArgumentException $e) {
            return $this->returnErrorResponse($e->getMessage(), $e->getCode());
        }

        return $handler->handle($request);
    }
}
