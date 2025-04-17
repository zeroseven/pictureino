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
    protected const int SIMILAR_SIZE_RANGE = 20;
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

        // Basis-Query für häufig verwendete Bildgrößen
        $queryBuilder
            ->select('width', 'height')
            ->addSelectLiteral('COUNT(*) as frequency')
            ->from(self::TABLE_NAME)
            ->where(
                $queryBuilder->expr()->and(
                    $queryBuilder->expr()->gte('width', $queryBuilder->createNamedParameter($this->configRequest->getWidth() - self::SIMILAR_SIZE_RANGE)),
                    $queryBuilder->expr()->lte('width', $queryBuilder->createNamedParameter($this->configRequest->getWidth() + self::SIMILAR_SIZE_RANGE)),
                    $queryBuilder->expr()->eq('ratio', $queryBuilder->createNamedParameter($this->aspectRatio ? (string)$this->aspectRatio : '')),
                    $queryBuilder->expr()->eq('height', $queryBuilder->createNamedParameter($this->configRequest->getHeight()))
                )
            );

        // Viewport-Einschränkung hinzufügen wenn vorhanden
        if ($this->configRequest->getViewport() > 0) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->eq('viewport', $queryBuilder->createNamedParameter($this->configRequest->getViewport()))
            );
        }

        $result = $queryBuilder
            ->groupBy('width', 'height', 'ratio')
            ->orderBy('frequency', 'DESC')
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();

        if ($result && isset($result['frequency']) && $result['frequency'] > 0) {
            $this->width = (int)$result['width'];
            $this->height = (int)$result['height'];
        } else {
            $this->width = (int)(ceil($this->configRequest->getWidth() / self::STEP_SIZE) * self::STEP_SIZE);
            $this->height = ($this->aspectRatio ?? GeneralUtility::makeInstance(AspectRatio::class, $this->configRequest->getWidth(), $this->configRequest->getHeight()))
                ->getHeight($this->width);
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
