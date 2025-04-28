<?php

declare(strict_types=1);

namespace Zeroseven\Picturerino\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\ProcessedFileRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class CleanupCommand extends Command
{
    private const TABLE_REQUEST = 'tx_picturerino_request';
    private const TABLE_REQUEST_PROCESSED = 'tx_picturerino_request_processed';

    protected ProcessedFileRepository $processedFileRepository;
    protected ConnectionPool $connectionPool;
    protected SymfonyStyle $io;

    public function __construct()
    {
        parent::__construct();
        $this->processedFileRepository = GeneralUtility::makeInstance(ProcessedFileRepository::class);
        $this->connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
    }

    protected function configure(): void
    {
        $this->setDescription('Cleanup all processed images')
            ->setHelp('Removes all processed images from storage, database and sys_file_processedfile.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);
        $this->io->title('Cleaning up processed images');

        $processedFileIds = $this->getProcessedFileIds();
        $deletedFiles = $this->deleteProcessedFiles($processedFileIds);
        $this->truncatePicturerinoTables();
        $this->removePhysicalFiles();

        $this->outputResults($deletedFiles);

        return Command::SUCCESS;
    }

    protected function getProcessedFileIds(): array
    {
        return $this->connectionPool
            ->getQueryBuilderForTable(self::TABLE_REQUEST_PROCESSED)
            ->select('processedfile')
            ->from(self::TABLE_REQUEST_PROCESSED)
            ->executeQuery()
            ->fetchFirstColumn();
    }

    protected function deleteProcessedFiles(array $processedFileIds): array
    {
        $result = [
            'total' => count($processedFileIds),
            'success' => 0
        ];

        if (empty($processedFileIds)) {
            return $result;
        }

        $this->io->progressStart(count($processedFileIds));

        foreach ($processedFileIds as $processedFileId) {
            try {
                $processedFile = $this->processedFileRepository->findByUid((int)$processedFileId);
                if ($processedFile instanceof ProcessedFile) {
                    $processedFile->delete(true);
                    $result['success']++;
                }
            } catch (\Exception $e) {
                $this->io->warning(sprintf('Could not delete processed file with ID %d: %s', $processedFileId, $e->getMessage()));
            }
            $this->io->progressAdvance();
        }

        $this->io->progressFinish();

        return $result;
    }

    protected function truncatePicturerinoTables(): void
    {
        foreach ([self::TABLE_REQUEST, self::TABLE_REQUEST_PROCESSED] as $table) {
            $connection = $this->connectionPool->getConnectionForTable($table);
            $connection->truncate($table);
        }
    }

    protected function removePhysicalFiles(): void
    {
        $storagePath = \TYPO3\CMS\Core\Core\Environment::getVarPath() . '/storage';
        if (is_dir($storagePath)) {
            GeneralUtility::rmdir($storagePath, true);
        }
    }

    protected function outputResults(array $deletedFiles): void
    {
        $this->io->section('Cleanup Results');
        $this->io->table(
            ['Processed Files', 'Count'],
            [
                ['Total', (string)$deletedFiles['total']],
                ['Successfully deleted', (string)$deletedFiles['success']]
            ]
        );

        $this->io->success('Cleanup completed successfully!');
    }
}
