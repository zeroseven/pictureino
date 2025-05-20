<?php

declare(strict_types=1);

namespace Zeroseven\Pictureino\Utility;

use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\InMemoryStorage;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class RateLimiterUtility
{
    private const IP_REQUEST_LIMIT = 100;
    private const IP_REQUEST_TIME = '1 hour';
    private const IMAGE_REQUEST_LIMIT = 20;
    private const IMAGE_REQUEST_TIME = '1 hour';

    private string $identifier;
    private $memory;

    public function __construct(string $identifier)
    {
        $this->identifier = $identifier;
        $this->memory = GeneralUtility::makeInstance(InMemoryStorage::class);
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
        ], $this->memory);

        return $rateLimit->create(md5($ip))->consume()->isAccepted();
    }

    protected function imageLimitExceeded(): bool
    {
        $rateLimit = GeneralUtility::makeInstance(RateLimiterFactory::class, [
            'id' => 'image',
            'policy' => 'token_bucket',
            'limit' => self::IMAGE_REQUEST_LIMIT,
            'rate' => ['interval' => self::IMAGE_REQUEST_TIME],
        ], $this->memory);
        die('lol');

        return $rateLimit->create($this->identifier)->consume()->isAccepted();
    }

    public function limitExceeded(): bool
    {
       return !$this->loggedIn()
        && !$this->ipLimitExceeded()
        && !$this->imageLimitExceeded();
    }
}
