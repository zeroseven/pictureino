<?php

declare(strict_types=1);

namespace Zeroseven\Picturerino\Utility;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Zeroseven\Picturerino\Entity\ConfigRequest;

class LogUtility
{
    protected const string TABLE_REQUEST = 'tx_picturerino_request';
    protected const string TABLE_REQUEST_PROCESSED = 'tx_picturerino_request_processed';

    protected string $identifier;
    protected ConfigRequest $configRequest;
    protected ImageUtility $imageUtility;
    protected MetricsUtility $metricsUtility;
    protected ConnectionPool $connectionPool;

    public function __construct(string $identifier, ConfigRequest $configRequest, ImageUtility $imageUtility, MetricsUtility $metricsUtility)
    {
        $this->identifier = $identifier;
        $this->configRequest = $configRequest;
        $this->imageUtility = $imageUtility;
        $this->metricsUtility = $metricsUtility;
        $this->connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
    }

    protected function getQueryBuilder(string $table): QueryBuilder
    {
        return $this->connectionPool->getQueryBuilderForTable($table);
    }

    protected function logRequest(): int
    {
        $queryBuilder = $this->getQueryBuilder(self::TABLE_REQUEST);
        $queryBuilder
            ->insert(self::TABLE_REQUEST)
            ->values([
                'identifier' => $this->identifier,
                'width' => $this->configRequest->getWidth() ?? 0,
                'height' => $this->configRequest->getHeight() ?? 0,
                'viewport' => $this->configRequest->getViewport() ?? 0,
                'ratio' => $this->metricsUtility->getAspectRatio() ?? '',
                'width_evaluated' => $this->metricsUtility->getWidth() ?? 0,
                'height_evaluated' => $this->metricsUtility->getHeight() ?? 0,
                'file' => $this->imageUtility->getFile()->getIdentifier(),
                'count' => 0,
                'tstamp' => time(),
                'crdate' => time(),
            ])->executeStatement();

        return (int) $queryBuilder->getConnection()->lastInsertId();
    }

    protected function logProcessedFile(int $requestId, ProcessedFile $processedFile): void
    {
        $queryBuilder = $this->getQueryBuilder(self::TABLE_REQUEST_PROCESSED);
        $queryBuilder
            ->insert(self::TABLE_REQUEST_PROCESSED)
            ->values([
                'uid_local' => $requestId,
                'uid_foreign' => $processedFile->getUid(),
            ])->executeStatement();
    }

    public function log(): void
    {
        if ($id = $this->logRequest()) {
            foreach ($this->imageUtility->getProcessedFiles() ?? [] as $processedFile) {
                $this->logProcessedFile($id, $processedFile);
            }
        }
    }
}
