<?php

declare(strict_types=1);

namespace Zeroseven\Pictureino\Utility;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Zeroseven\Pictureino\Entity\AspectRatio;
use Zeroseven\Pictureino\Entity\ConfigRequest;

class MetricsUtility
{
    protected const TABLE_NAME = 'tx_pictureino_request';
    protected const SIMILAR_SIZE_RANGE = [-10, 40];
    protected const STEP_SIZE = 100;

    protected string $identifier;
    protected ConfigRequest $configRequest;
    protected ImageUtility $imageUtility;
    protected SettingsUtility $settingsUtility;
    protected Connection $connection;
    protected ?AspectRatio $aspectRatio = null;
    protected ?int $width = null;
    protected ?int $height = null;
    protected ?bool $limitExceeded = null;

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

    /** @throws \InvalidArgumentException */
    public function validate(): bool
    {
        if ($this->aspectRatio && abs($this->aspectRatio->getHeight($this->configRequest->getWidth()) - $this->configRequest->getHeight()) > $this->configRequest->getHeight() * 0.03) {
            throw new \InvalidArgumentException('The aspect ratio is invalid.', 1745092982);
        }

        if ($this->configRequest->getWidth() > $this->configRequest->getViewport()) {
            throw new \InvalidArgumentException('Width exceeds the viewport.', 1745092983);
        }

        if ($this->configRequest->getWidth() <= 0 || $this->configRequest->getHeight() <= 0) {
            throw new \InvalidArgumentException('Width or height must be greater than zero.', 1745092984);
        }

        if ($maxImageDimensions = (int) ($this->configRequest->getConfig()['maxImageDimensions'] ?? $this->settingsUtility->get('maxImageDimensions'))) {
            if ($this->configRequest->getWidth() > $maxImageDimensions || $this->configRequest->getHeight() > $maxImageDimensions) {
                throw new \InvalidArgumentException('Dimensions exceeds the maximum lenght.', 1745092985);
            }
        }

        return true;
    }

    protected function getMatches(int $requestedWidth, int $requestedHeight): array|false
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(self::TABLE_NAME);

        return $queryBuilder
            ->select('width', 'height')
            ->addSelectLiteral(
                'SUM(count) as total_count',
                'ABS(width - ' . $queryBuilder->createNamedParameter($requestedWidth, Connection::PARAM_INT) . ') AS width_diff',
                'ABS(height - ' . $queryBuilder->createNamedParameter($requestedHeight, Connection::PARAM_INT) . ') AS height_diff'
            )
            ->from(self::TABLE_NAME)
            ->where(
                $queryBuilder->expr()->eq('identifier', $queryBuilder->createNamedParameter($this->identifier, Connection::PARAM_STR))
            )
            ->andWhere(
                'width - ' . $queryBuilder->createNamedParameter($requestedWidth, Connection::PARAM_INT) . ' BETWEEN ' .
                $queryBuilder->createNamedParameter(self::SIMILAR_SIZE_RANGE[0], Connection::PARAM_INT) . ' AND ' .
                $queryBuilder->createNamedParameter(self::SIMILAR_SIZE_RANGE[1], Connection::PARAM_INT)
            )
            ->andWhere(
                'height - ' . $queryBuilder->createNamedParameter($requestedHeight, Connection::PARAM_INT) . ' BETWEEN ' .
                $queryBuilder->createNamedParameter(self::SIMILAR_SIZE_RANGE[0], Connection::PARAM_INT) . ' AND ' .
                $queryBuilder->createNamedParameter(self::SIMILAR_SIZE_RANGE[1], Connection::PARAM_INT)
            )
            ->andWhere(
                $queryBuilder->expr()->eq('aspect_ratio', $queryBuilder->createNamedParameter((string) $this->aspectRatio, Connection::PARAM_STR))
            )
            ->groupBy('width', 'height', 'width_diff', 'height_diff')
            ->orderBy('total_count', 'DESC')
            ->addOrderBy('width_diff', 'ASC')
            ->addOrderBy('height_diff', 'ASC')
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();
    }

    public function getAlternativeMatch(int $requestedWidth): array|false
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(self::TABLE_NAME);

        return $queryBuilder
            ->select('width', 'height')
            ->from(self::TABLE_NAME)
            ->where(
                $queryBuilder->expr()->eq('identifier', $queryBuilder->createNamedParameter($this->identifier, Connection::PARAM_STR))
            )
            ->andWhere(
                $queryBuilder->expr()->eq('aspect_ratio', $queryBuilder->createNamedParameter((string) $this->aspectRatio, Connection::PARAM_STR))
            )
            ->andWhere(
                $queryBuilder->expr()->gte('width', $queryBuilder->createNamedParameter($requestedWidth, Connection::PARAM_INT))
            )
            ->orderBy('width', 'ASC')
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();
    }

    protected function checkRequestLimit(): bool
    {
        return $this->limitExceeded ??= GeneralUtility::makeInstance(RateLimiterUtility::class, $this->identifier)->limitExceeded();
    }

    protected function evaluate(): void
    {
        $requestedWidth = $this->configRequest->getWidth();
        $requestedHeight = $this->configRequest->getHeight();

        $result = $this->getMatches($requestedWidth, $requestedHeight);

        if ($result && isset($result['width'], $result['height'])) {
            $this->width = (int) $result['width'];
            $this->height = (int) $result['height'];
        } else {
            if ($this->checkRequestLimit()) {
                if ($result = $this->getAlternativeMatch($requestedWidth)) {
                    $this->width = (int) $result['width'];
                    $this->height = (int) $result['height'];
                } else {
                    $this->width = 1000;
                    $this->height = $this->aspectRatio?->getHeight(1000) ?? 1000;
                }
            } else {
                $this->width = (int) (ceil($requestedWidth / self::STEP_SIZE) * self::STEP_SIZE);
                $this->height = $this->aspectRatio?->getHeight($this->width) ?? (int) (ceil($requestedHeight / self::STEP_SIZE) * self::STEP_SIZE);
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
