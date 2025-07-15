<?php

declare(strict_types=1);

namespace Zeroseven\Pictureino\Utility;

use Doctrine\DBAL\Exception as DBALException;
use Exception;
use InvalidArgumentException;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Zeroseven\Pictureino\Entity\AspectRatio;
use Zeroseven\Pictureino\Entity\ConfigRequest;

class MetricsUtility
{
    protected const TABLE_NAME = 'tx_pictureino_request';
    protected const TOLERANCE = [-10, 50];

    protected string $identifier;
    protected ConfigRequest $configRequest;
    protected ImageUtility $imageUtility;
    protected SettingsUtility $settingsUtility;
    protected Connection $connection;
    protected ?AspectRatio $aspectRatio = null;
    protected ?int $width = null;
    protected ?int $height = null;
    protected ?bool $limitExceeded = null;

    /** @throws Exception */
    public function __construct(string $identifier, ConfigRequest $configRequest, ImageUtility $imageUtility, SettingsUtility $settingsUtility)
    {
        $this->identifier = $identifier;
        $this->configRequest = $configRequest;
        $this->imageUtility = $imageUtility;
        $this->settingsUtility = $settingsUtility;
        $this->connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable(self::TABLE_NAME);
        $this->aspectRatio = GeneralUtility::makeInstance(AspectRatioUtility::class)
            ->addList($configRequest->getConfig()['aspectRatio'] ?? null)
            ->getAspectForWidth($this->configRequest->getViewport());

        $this->evaluate();
    }

    /** @throws InvalidArgumentException */
    public function validate(): bool
    {
        if ($this->aspectRatio && abs($this->aspectRatio->getHeight($this->configRequest->getWidth()) - $this->configRequest->getHeight()) > $this->configRequest->getHeight() * 0.03) {
            throw new InvalidArgumentException('The aspect ratio is invalid.', 1745092982);
        }

        if ($this->configRequest->getWidth() > $this->configRequest->getViewport()) {
            throw new InvalidArgumentException('Width exceeds the viewport.', 1745092983);
        }

        if ($this->configRequest->getWidth() <= 0 || $this->configRequest->getHeight() <= 0) {
            throw new InvalidArgumentException('Width or height must be greater than zero.', 1745092984);
        }

        if ($maxImageDimensions = (int) ($this->configRequest->getConfig()['maxImageDimensions'] ?? $this->settingsUtility->get('maxImageDimensions'))) {
            if ($this->configRequest->getWidth() > $maxImageDimensions || $this->configRequest->getHeight() > $maxImageDimensions) {
                throw new InvalidArgumentException('Dimensions exceed the maximum length.', 1745092985);
            }
        }

        return true;
    }

    protected function getMatches(): array|false
    {
        $requestedWidth = $this->configRequest->getWidth();
        $requestedHeight = $this->configRequest->getHeight();

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(self::TABLE_NAME);

        $result = $queryBuilder
            ->select('width_evaluated', 'height_evaluated')
            ->addSelectLiteral('ABS(width_evaluated - ' . $queryBuilder->createNamedParameter($requestedWidth, Connection::PARAM_INT) . ') AS diff')
            ->from(self::TABLE_NAME)
            ->where(
                $queryBuilder->expr()->eq('identifier', $queryBuilder->createNamedParameter($this->identifier, Connection::PARAM_STR))
            )
            ->andWhere(
                $queryBuilder->expr()->gt('width_evaluated', $queryBuilder->createNamedParameter($requestedWidth + self::TOLERANCE[0], Connection::PARAM_INT)),
                $queryBuilder->expr()->lt('width_evaluated', $queryBuilder->createNamedParameter($requestedWidth + self::TOLERANCE[1], Connection::PARAM_INT))
            )
            ->andWhere(
                $queryBuilder->expr()->gt('height_evaluated', $queryBuilder->createNamedParameter($requestedHeight + self::TOLERANCE[0], Connection::PARAM_INT)),
                $queryBuilder->expr()->lt('height_evaluated', $queryBuilder->createNamedParameter($requestedHeight + self::TOLERANCE[1], Connection::PARAM_INT))
            )
            ->andWhere(
                $queryBuilder->expr()->eq('aspect_ratio', $queryBuilder->createNamedParameter((string) $this->aspectRatio, Connection::PARAM_STR))
            )
            ->orderBy('diff', 'ASC')
            ->setMaxResults(1)
            ->executeQuery();

        try {
            return $result->fetchAssociative();
        } catch (DBALException) {
            return false;
        }
    }

    public function getAlternativeMatch(): array|false
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(self::TABLE_NAME);

        $result = $queryBuilder
            ->select('width_evaluated', 'height_evaluated')
            ->from(self::TABLE_NAME)
            ->where(
                $queryBuilder->expr()->eq('identifier', $queryBuilder->createNamedParameter($this->identifier, Connection::PARAM_STR))
            )
            ->andWhere(
                $queryBuilder->expr()->eq('aspect_ratio', $queryBuilder->createNamedParameter((string) $this->aspectRatio, Connection::PARAM_STR))
            )
            ->andWhere(
                $queryBuilder->expr()->gte('width_evaluated', $queryBuilder->createNamedParameter($this->configRequest->getWidth(), Connection::PARAM_INT))
            )
            ->orderBy('width_evaluated', 'ASC')
            ->setMaxResults(1)
            ->executeQuery();

        try {
            return $result->fetchAssociative();
        } catch (DBALException) {
            return false;
        }
    }

    protected function checkRequestLimit(): bool
    {
        return $this->limitExceeded ??= GeneralUtility::makeInstance(RateLimiterUtility::class, $this->identifier)->limitExceeded();
    }

    protected function evaluate(): void
    {
        if (($result = $this->getMatches()) && isset($result['width_evaluated'], $result['height_evaluated'])) {
            $this->width = (int) $result['width_evaluated'];
            $this->height = (int) $result['height_evaluated'];
        } else {
            if ($this->checkRequestLimit()) {
                if ($result = $this->getAlternativeMatch()) {
                    $this->width = (int) $result['width_evaluated'];
                    $this->height = (int) $result['height_evaluated'];
                } else {
                    $this->width = 1000;
                    $this->height = $this->aspectRatio?->getHeight(1000) ?? 1000;
                }
            } else {
                $this->width = $this->configRequest->getWidth();
                $this->height = $this->aspectRatio?->getHeight($this->width) ?? $this->configRequest->getHeight();
            }
        }
    }

    public function getAspectRatio(): ?AspectRatio
    {
        return $this->aspectRatio;
    }

    public function getWidth(): ?int
    {
        return $this->width;
    }

    public function getHeight(): ?int
    {
        return $this->height;
    }

    public function limitExceeded(): bool
    {
        return (bool) $this->limitExceeded;
    }

    public function toArray(): array
    {
        return [
            'aspectRatio' => $this->getAspectRatio()?->toArray(),
            'width' => $this->getWidth(),
            'height' => $this->getHeight(),
            'limitExceeded' => $this->limitExceeded(),
        ];
    }
}
