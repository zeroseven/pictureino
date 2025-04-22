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
            ->setAspectRatios($configRequest->getConfig()['aspectRatio'] ?? null)
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

        $maxWidth = (int) ($this->configRequest->getConfig()['image_max_width'] ?? $this->settingsUtility->get('image_max_width'));
        if (0 === $maxWidth || $this->configRequest->getWidth() > $maxWidth) {
            throw new \InvalidArgumentException('Width exceeds the maximum width.', 1745092985);
        }

        return true;
    }

    protected function evaluate(): void
    {
        $requestedWidth = $this->configRequest->getWidth();

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(self::TABLE_NAME);

        $result = $queryBuilder
            ->selectLiteral(
                'width',
                'COUNT(*) AS frequency',
                'ABS(width - ' . $queryBuilder->createNamedParameter($requestedWidth) . ') AS width_diff'
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
            ->groupBy('width', 'height', 'width_diff')
            ->orderBy('frequency', 'DESC')
            ->addOrderBy('width_diff', 'ASC')
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();

        // Use found metrics or calculate new size
        if ($result && isset($result['width'])) {
            $this->width = (int) $result['width'];
        } else {
            $this->width = (int) (ceil($requestedWidth / self::STEP_SIZE) * self::STEP_SIZE);
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
        return $this->aspectRatio?->getHeight($this->width);
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
