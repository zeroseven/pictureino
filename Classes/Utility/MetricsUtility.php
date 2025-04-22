<?php

declare(strict_types=1);

namespace Zeroseven\Picturerino\Utility;

use InvalidArgumentException;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Zeroseven\Picturerino\Entity\AspectRatio;
use Zeroseven\Picturerino\Entity\ConfigRequest;
use Zeroseven\Picturerino\Utility\AspectRatioUtility;
use Zeroseven\Picturerino\Utility\SettingsUtility;

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

    public function __construct(string $identifier, ConfigRequest $configRequest, ImageUtility $imageUtility, SettingsUtility $settingsUtility) {
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
               throw new InvalidArgumentException('The aspect ratio is invalid.', 1745092982);
           }

           if ($this->configRequest->getWidth() > $this->configRequest->getViewport()) {
               throw new InvalidArgumentException('Width exceeds the viewport.', 1745092983);
           }

           if ($this->configRequest->getWidth() <= 0 || $this->configRequest->getHeight() <= 0) {
               throw new InvalidArgumentException('Width or height must be greater than zero.', 1745092984);
           }

           $maxWidth = (int)($this->configRequest->getConfig()['image_max_width'] ?? $this->settingsUtility->get('image_max_width'));
           if ($maxWidth === 0 || $this->configRequest->getWidth() > $maxWidth) {
               throw new InvalidArgumentException('Width exceeds the maximum width.', 1745092985);
           }

           return true;
       }


    protected function evaluate(): void {
        /**
         * Find similar size by:
         * 1. Filter by identifier
         * 2. Check width difference using MySQL ABS function
         * 3. Get most frequent size within range
         */
        $requestedWidth = $this->configRequest->getWidth();

        $sql = '
            SELECT width, COUNT(*) as frequency
            FROM ' . self::TABLE_NAME . '
            WHERE identifier = ?
            AND (width - ?) BETWEEN ? AND ?
            GROUP BY width, height
            ORDER BY frequency DESC, ABS(width - ?) ASC
            LIMIT 1
        ';

        $stmt = $this->connection->executeQuery(
            $sql,
            [
                $this->identifier,
                $requestedWidth,
                self::SIMILAR_SIZE_RANGE[0],
                self::SIMILAR_SIZE_RANGE[1],
                $requestedWidth
            ]
        );

        $similarSize = $stmt->fetchAssociative();

        // Use found metrics or calculate new size
        if ($similarSize && isset($similarSize['width'])) {
            $this->width = (int)$similarSize['width'];
        } else {
            $this->width = (int)(ceil($requestedWidth / self::STEP_SIZE) * self::STEP_SIZE);
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

    public function getHeight(): ?int {
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
            'height' => $this->getHeight()
        ];
    }
}
