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
    protected const string TABLE_NAME = 'tx_picturerino_metrics';
    protected const array SIMILAR_SIZE_RANGE = [-5, 30];
    protected const int STEP_SIZE = 50;

    protected string $identifier;
    protected ConfigRequest $configRequest;
    protected ImageUtility $imageUtility;
    protected ?AspectRatio $aspectRatio = null;
    protected Connection $connection;
    protected ?int $width = null;
    protected ?int $height = null;

    public function __construct(string $identifier, ConfigRequest $configRequest, ImageUtility $imageUtility, ?AspectRatio $aspectRatio = null) {
        $this->identifier = $identifier;
        $this->configRequest = $configRequest;
        $this->imageUtility = $imageUtility;
        $this->aspectRatio = $aspectRatio;
        $this->connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable(self::TABLE_NAME);

        $this->evaluate();
    }

    public function log(): void {
        $this->connection->insert(
            self::TABLE_NAME,
            [
                'identifier' => $this->identifier,
                'width' => $this->configRequest->getWidth(),
                'height' => $this->configRequest->getHeight(),
                'viewport' => $this->configRequest->getViewport(),
                'ratio' => (string)($this->aspectRatio ?? ''),
                'width_evaluated' => (int)$this->width,
                'height_evaluated' => (int)$this->height,
                'file' => $this->imageUtility->getFile()->getIdentifier(),
                'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
                'tstamp' => time(),
                'crdate' => time()
            ]
        );
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
}
