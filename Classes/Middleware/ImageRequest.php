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
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use Zeroseven\Picturerino\Entity\ConfigRequest;
use Zeroseven\Picturerino\Utility\ImageUtility;
use Zeroseven\Picturerino\Utility\LogUtility;
use Zeroseven\Picturerino\Utility\MetricsUtility;
use Zeroseven\Picturerino\Utility\SettingsUtility;

class ImageRequest implements MiddlewareInterface
{
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
        if ($this->logUtility->hasExistingEntry() && !GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('backend.user', 'isLoggedIn')) {



        }

        return false;
    }

    public function processImage(): array
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

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            $headers = [
                'cache-control' => 'no-store, no-cache, must-revalidate, max-age=0',
                'x-robots' => 'noindex, nofollow',
            ];

            if ($this->initializeConfig($request)) {
                if ($this->tooManyRequests()) {
                    return new JsonResponse(['error' => 'Too many requests'], 429, $headers);
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

                return new JsonResponse($data, 200, $headers);
            }
        } catch (InvalidArgumentException $e) {
            return new JsonResponse(['error' => [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ]], 400, $headers);
        }

        return $handler->handle($request);
    }
}
