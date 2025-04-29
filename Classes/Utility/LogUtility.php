<?php

declare(strict_types=1);

namespace Zeroseven\Pictureino\Utility;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Zeroseven\Pictureino\Entity\ConfigRequest;

class LogUtility
{
    protected const string TABLE_REQUEST = 'tx_pictureino_request';
    protected const string TABLE_REQUEST_PROCESSED = 'tx_pictureino_request_processed';

    protected string $identifier;
    protected ConfigRequest $configRequest;
    protected ImageUtility $imageUtility;
    protected MetricsUtility $metricsUtility;
    protected ConnectionPool $connectionPool;
    protected ?array $existingEntry = null;

    public function __construct(string $identifier, ConfigRequest $configRequest, ImageUtility $imageUtility, MetricsUtility $metricsUtility)
    {
        $this->identifier = $identifier;
        $this->configRequest = $configRequest;
        $this->imageUtility = $imageUtility;
        $this->metricsUtility = $metricsUtility;
        $this->connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $this->existingEntry = $this->getExistingEntry();
    }

    protected function getQueryBuilder(string $table): QueryBuilder
    {
        return $this->connectionPool->getQueryBuilderForTable($table);
    }

    protected function getExistingEntry(): ?array
    {
        if (null === $this->existingEntry) {
            $queryBuilder = $this->getQueryBuilder(self::TABLE_REQUEST);

            $existingEntry = $queryBuilder
                ->select('uid', 'count')
                ->from(self::TABLE_REQUEST)
                ->where(
                    $queryBuilder->expr()->eq('identifier', $queryBuilder->createNamedParameter($this->identifier)),
                    $queryBuilder->expr()->eq('width', $queryBuilder->createNamedParameter($this->configRequest->getWidth() ?? 0)),
                    $queryBuilder->expr()->eq('height', $queryBuilder->createNamedParameter($this->configRequest->getHeight() ?? 0)),
                    $queryBuilder->expr()->eq('width_evaluated', $queryBuilder->createNamedParameter($this->metricsUtility->getWidth() ?? 0)),
                    $queryBuilder->expr()->eq('height_evaluated', $queryBuilder->createNamedParameter($this->metricsUtility->getHeight() ?? 0))
                )
                ->executeQuery()
                ->fetchAssociative();

            return $this->existingEntry = $existingEntry ?: [];
        }

        return $this->existingEntry;
    }

    protected function getExtensionVersion(): string
    {
        $_EXTKEY = 'pictureino';

        if (($path = ExtensionManagementUtility::extPath($_EXTKEY, 'ext_emconf.php')) && file_exists($path)) {
            include $path;

            return $EM_CONF[$_EXTKEY]['version'] ?? '';
        }

        return '';
    }

    protected function countRequest(): void
    {
        if ($this->hasExistingEntry()) {
            $queryBuilder = $this->getQueryBuilder(self::TABLE_REQUEST);
            $queryBuilder
                ->update(self::TABLE_REQUEST)
                ->where(
                    $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($this->existingEntry['uid']))
                )
                ->set('count', (int) ($this->existingEntry['count'] ?? 0) + 1)
                ->set('tstamp', time())
                ->executeStatement();
        }
    }

    public function hasExistingEntry(): bool
    {
        $existingEntry = $this->getExistingEntry();

        return $existingEntry && (int) ($existingEntry['uid'] ?? 0) > 0;
    }

    protected function createRequest(): int
    {
        $queryBuilder = $this->getQueryBuilder(self::TABLE_REQUEST);
        $queryBuilder
            ->insert(self::TABLE_REQUEST)
            ->values([
                'identifier' => $this->identifier,
                'width' => $this->configRequest->getWidth() ?? 0,
                'height' => $this->configRequest->getHeight() ?? 0,
                'viewport' => $this->configRequest->getViewport() ?? 0,
                'aspect_ratio' => $this->metricsUtility->getAspectRatio() ?? '',
                'width_evaluated' => $this->metricsUtility->getWidth() ?? 0,
                'height_evaluated' => $this->metricsUtility->getHeight() ?? 0,
                'file' => $this->imageUtility->getFile()->getIdentifier(),
                'count' => 1,
                'version' => $this->getExtensionVersion(),
                'tstamp' => time(),
                'crdate' => time(),
            ])->executeStatement();

        return (int) $queryBuilder->getConnection()->lastInsertId();
    }

    protected function logRequest(): ?int
    {
        if ($this->hasExistingEntry()) {
            $this->countRequest();

            return null;
        }

        return $this->createRequest();
    }

    protected function logProcessedFile(int $requestId, ProcessedFile $processedFile): void
    {
        $queryBuilder = $this->getQueryBuilder(self::TABLE_REQUEST_PROCESSED);
        $queryBuilder
            ->insert(self::TABLE_REQUEST_PROCESSED)
            ->values([
                'request' => $requestId,
                'processedfile' => $processedFile->getUid(),
            ])->executeStatement();
    }

    public function log(): void
    {
        if ($newRequestId = $this->logRequest()) {
            foreach ($this->imageUtility->getProcessedFiles() ?? [] as $processedFile) {
                $this->logProcessedFile($newRequestId, $processedFile);
            }
        }
    }
}
