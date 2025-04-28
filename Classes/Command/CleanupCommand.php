<?php

declare(strict_types=1);

namespace Zeroseven\Picturerino\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\ProcessedFileRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class CleanupCommand extends Command
{
    protected function configure(): void
    {
        $this->setDescription('Cleanup all processed images')
            ->setHelp('Removes all processed images from storage, database and sys_file_processedfile.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Get processedfile IDs from picturerino request table
        $connection = GeneralUtility::makeInstance(ConnectionPool::class);
        $queryBuilder = $connection->getQueryBuilderForTable('tx_picturerino_request_processed');
        $processedFileIds = $queryBuilder
            ->select('processedfile')
            ->from('tx_picturerino_request_processed')
            ->executeQuery()
            ->fetchFirstColumn();

        // Delete processed files using the TYPO3 Core Repository
        if (!empty($processedFileIds)) {
            $processedFileRepository = GeneralUtility::makeInstance(ProcessedFileRepository::class);
            foreach ($processedFileIds as $processedFileId) {
                try {
                    $processedFile = $processedFileRepository->findByUid((int)$processedFileId);
                    if ($processedFile) {
                        $processedFile->delete(true);
                    }
                } catch (\Exception $e) {
                    $io->error('Could not delete processed file with ID ' . $processedFileId);
                }
            }
        }

        // Clear picturerino tables
        $connection->getConnectionForTable('tx_picturerino_request_processed')
            ->truncate('tx_picturerino_request_processed');

        $connection->getConnectionForTable('tx_picturerino_request')
            ->truncate('tx_picturerino_request');


        $io->success('All processed images have been removed.');

        return Command::SUCCESS;
    }
}
