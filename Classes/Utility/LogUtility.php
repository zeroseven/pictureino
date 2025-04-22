<?php

declare(strict_types=1);

namespace Zeroseven\Picturerino\Utility;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Zeroseven\Picturerino\Entity\AspectRatio;
use Zeroseven\Picturerino\Entity\ConfigRequest;

class LogUtility
{
    protected const string TABLE_REQUEST = 'tx_picturerino_request';
    protected const string TABLE_REQUEST_PROCESSED = 'tx_picturerino_request_processed';

    protected string $identifier;
    protected ConfigRequest $configRequest;
    protected ImageUtility $imageUtility;
    protected MetricsUtility $metricsUtility;
    protected ?AspectRatio $aspectRatio;
    protected ConnectionPool $connectionPool;

    public function __construct(string $identifier, ConfigRequest $configRequest, ImageUtility $imageUtility, MetricsUtility $metricsUtility, ?AspectRatio $aspectRatio = null)
    {
        $this->identifier = $identifier;
        $this->configRequest = $configRequest;
        $this->imageUtility = $imageUtility;
        $this->metricsUtility = $metricsUtility;
        $this->aspectRatio = $aspectRatio;
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
                'width' => $this->configRequest->getWidth(),
                'height' => $this->configRequest->getHeight(),
                'viewport' => $this->configRequest->getViewport(),
                'ratio' => $this->aspectRatio ? (string)$this->aspectRatio : '',
                'width_evaluated' => $this->metricsUtility->getWidth(),
                'height_evaluated' => $this->metricsUtility->getHeight(),
                'file' => $this->imageUtility->getFile()->getIdentifier(),
                'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
                'tstamp' => time(),
                'crdate' => time()
            ])->executeStatement();

        return (int)$queryBuilder->getConnection()->lastInsertId();
    }

    protected function logProcessedFile(int $requestId, ProcessedFile $processedFile): void
    {
        $queryBuilder = $this->getQueryBuilder(self::TABLE_REQUEST_PROCESSED);
        $queryBuilder
            ->insert(self::TABLE_REQUEST_PROCESSED)
            ->values([
                'uid_local' => $requestId,
                'uid_foreign' => $processedFile->getUid()
            ])->executeStatement();
    }

    public function log(): void
    {
        if($id = $this->logRequest()) {
            foreach ($this->imageUtility->getProcessedFiles() ?? [] as $processedFile) {
                $this->logProcessedFile($id, $processedFile);
            }
        }
    }
}
