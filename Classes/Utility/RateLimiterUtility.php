<?php

declare(strict_types=1);

namespace Zeroseven\Pictureino\Utility;

use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\CacheStorage;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class RateLimiterUtility
{
    private const IP_REQUEST_LIMIT = 4;
    private const IP_REQUEST_TIME = '1 hour';
    private const IMAGE_REQUEST_LIMIT = 2;
    private const IMAGE_REQUEST_TIME = '1 hour';

    private string $identifier;
    private FrontendInterface $cache;

    public function __construct(string $identifier)
    {
        $this->identifier = $identifier;
    }

    public function injectCache(FrontendInterface $cache): void
    {
        $this->cache = $cache;
    }

    protected function getStorage(): CacheStorage
    {
        return GeneralUtility::makeInstance(CacheStorage::class, $this->cache);
    }

    protected function loggedIn(): bool
    {
        return (bool)(GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('backend.user', 'isLoggedIn') ?? false);
    }

    protected function ipLimitExceeded(): bool
    {
        $ip = GeneralUtility::getIndpEnv('REMOTE_ADDR');
        $rateLimit = GeneralUtility::makeInstance(RateLimiterFactory::class, [
            'id' => 'ip',
            'policy' => 'token_bucket',
            'limit' => self::IP_REQUEST_LIMIT,
            'rate' => ['interval' => self::IP_REQUEST_TIME],
        ], $this->getStorage());

        return $rateLimit->create(md5($ip))->consume()->isAccepted();
    }

    protected function imageLimitExceeded(): bool
    {
        $rateLimit = GeneralUtility::makeInstance(RateLimiterFactory::class, [
            'id' => 'image',
            'policy' => 'token_bucket',
            'limit' => self::IMAGE_REQUEST_LIMIT,
            'rate' => ['interval' => self::IMAGE_REQUEST_TIME],
        ], $this->getStorage());
        die('lol');
        die('"3');

        return $rateLimit->create($this->identifier)->consume()->isAccepted();
    }

    public function limitExceeded(): bool
    {
        return $this->ipLimitExceeded();
       return !$this->loggedIn()
        && !$this->ipLimitExceeded()
        && !$this->imageLimitExceeded();
    }
}
