<?php

declare(strict_types=1);

namespace Zeroseven\Picturerino\Utility;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Zeroseven\Picturerino\Entity\AspectRatio;
use Zeroseven\Picturerino\Entity\ConfigRequest;

class MetricsUtility
{
    protected const string TABLE_NAME = 'tx_picturerino_request';
    protected const array SIMILAR_SIZE_RANGE = [-5, 30];
    protected const int STEP_SIZE = 50;

    protected string $identifier;
    protected ConfigRequest $configRequest;
    protected ImageUtility $imageUtility;
    protected SettingsUtility $settingsUtility;
    protected ?AspectRatio $aspectRatio = null;
    protected Connection $connection;
    protected ?int $width = null;
    protected ?int $height = null;

    public function __construct(string $identifier, ConfigRequest $configRequest, ImageUtility $imageUtility, SettingsUtility $settingsUtility)
    {
        $this->identifier = $identifier;
        $this->configRequest = $configRequest;
        $this->imageUtility = $imageUtility;
        $this->settingsUtility = $settingsUtility;
        $this->aspectRatio = GeneralUtility::makeInstance(AspectRatioUtility::class)
            ->set($configRequest->getConfig()['aspectRatio'] ?? null)
            ->getAspectForWidth($this->configRequest->getViewport());
        $this->connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable(self::TABLE_NAME);

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

        if($maxImageDimensions = (int)($this->configRequest->getConfig()['maxImageDimensions'] ?? $this->settingsUtility->get('maxImageDimensions'))) {
            if ($this->configRequest->getWidth() > $maxImageDimensions || $this->configRequest->getHeight() > $maxImageDimensions) {
                throw new \InvalidArgumentException('Dimensions exceeds the maximum lenght.', 1745092985);
            }
        }

        return true;
    }

    protected function evaluate(): void
    {
        $requestedWidth = $this->configRequest->getWidth();
        $requestedHeight = $this->configRequest->getHeight();

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(self::TABLE_NAME);

        $result = $queryBuilder
            ->select('width', 'height')
            ->addSelectLiteral(
                'SUM(count) as total_count',
                'ABS(width - ' . $queryBuilder->createNamedParameter($requestedWidth) . ') AS width_diff',
                'ABS(height - ' . $queryBuilder->createNamedParameter($requestedHeight) . ') AS height_diff'
            )
            ->from(self::TABLE_NAME)
            ->where(
                $queryBuilder->expr()->eq('identifier', $queryBuilder->createNamedParameter($this->identifier))
            )
            ->andWhere(
                'width - ' . $queryBuilder->createNamedParameter($requestedWidth) . ' BETWEEN ' .
                $queryBuilder->createNamedParameter(self::SIMILAR_SIZE_RANGE[0]) . ' AND ' .
                $queryBuilder->createNamedParameter(self::SIMILAR_SIZE_RANGE[1])
            )
            ->andWhere(
                'height - ' . $queryBuilder->createNamedParameter($requestedHeight) . ' BETWEEN ' .
                $queryBuilder->createNamedParameter(self::SIMILAR_SIZE_RANGE[0]) . ' AND ' .
                $queryBuilder->createNamedParameter(self::SIMILAR_SIZE_RANGE[1])
            )
            ->groupBy('width', 'height', 'width_diff', 'height_diff')
            ->orderBy('total_count', 'DESC')
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();

        // Use found metrics or calculate new sizes
        if ($result && isset($result['width'], $result['height'])) {
            $this->width = (int)$result['width'];
            $this->height = (int)$result['height'];
        } else {
            $this->width = (int)(ceil($requestedWidth / self::STEP_SIZE) * self::STEP_SIZE);
            $this->height = $this->aspectRatio?->getHeight($this->width) ?? (int)(ceil($requestedHeight / self::STEP_SIZE) * self::STEP_SIZE);
        }
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getWidth(): ?int
    {
        return $this->width;
    }

    public function getHeight(): ?int
    {
        return $this->height;
    }

    public function getAspectRatio(): ?AspectRatio
    {
        return $this->aspectRatio;
    }

    public function toArray(): array
    {
        return [
            'identifier' => $this->getIdentifier(),
            'aspectRatio' => $this->getAspectRatio()?->toArray(),
            'width' => $this->getWidth(),
            'height' => $this->getHeight(),
        ];
    }
}
