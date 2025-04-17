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
    protected const int SIMILAR_SIZE_RANGE = [-5 + 20];
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
                'ratio' => $this->aspectRatio ? (string)$this->aspectRatio : '',
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
        $queryBuilder = $this->connection->createQueryBuilder();

        // 1. Hole die 10 am häufigsten verwendeten Metriken für diesen identifier
        $commonMetrics = $queryBuilder
            ->select('width', 'height')
            ->addSelectLiteral('COUNT(*) as frequency')
            ->from(self::TABLE_NAME)
            ->where(
                $queryBuilder->expr()->eq('identifier', $queryBuilder->createNamedParameter($this->identifier))
            )
            ->groupBy('width', 'height')
            ->orderBy('frequency', 'DESC')
            ->setMaxResults(10)
            ->executeQuery()
            ->fetchAllAssociative();

        // 2. Prüfe auf ähnliche Größen
        $requestedWidth = $this->configRequest->getWidth();
        $similarSize = null;
        $highestFrequency = 0;

        foreach ($commonMetrics as $metric) {
            $widthDiff = abs($metric['width'] - $requestedWidth);

            // Prüfe ob die Größe im erlaubten Bereich liegt
            if ($widthDiff <= self::SIMILAR_SIZE_RANGE) {
                // Wenn mehrere Größen gefunden werden, nimm die häufigste
                if ($metric['frequency'] > $highestFrequency) {
                    $similarSize = $metric;
                    $highestFrequency = $metric['frequency'];
                }
            }
        }

        // 3. Wenn ähnliche Größe gefunden wurde, verwende diese
        if ($similarSize !== null) {
            $this->width = (int)$similarSize['width'];
            $this->height = (int)$similarSize['height'];
        } else {
            // 4. Sonst runde auf nächste STEP_SIZE
            $this->width = (int)(ceil($requestedWidth / self::STEP_SIZE) * self::STEP_SIZE);
            $this->height = $this->configRequest->getHeight();
        }
    }

    public function getWidth(): ?int
    {
        return $this->width;
    }

    public function getHeight(): ?int {
        return $this->height;
    }
}
