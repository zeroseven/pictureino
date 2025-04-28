<?php

declare(strict_types=1);

namespace Zeroseven\Picturerino\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class CleanupCommand extends Command
{
    protected function configure(): void
    {
        $this->setDescription('Cleanup all processed images')
            ->setHelp('Removes all processed images from storage and database.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('tx_picturerino_request');

        // Clear processed files relation table
        $connection->truncate('tx_picturerino_request_processed');

        // Clear request table
        $connection->truncate('tx_picturerino_request');

        // Remove physical files
        $storagePath = \TYPO3\CMS\Core\Core\Environment::getVarPath() . '/storage';
        if (is_dir($storagePath)) {
            GeneralUtility::rmdir($storagePath, true);
        }

        $io->success('All processed images have been removed.');

        return Command::SUCCESS;
    }
}
