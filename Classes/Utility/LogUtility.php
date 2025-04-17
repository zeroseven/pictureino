<?php

declare(strict_types=1);

namespace Zeroseven\Picturerino\Utility;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Zeroseven\Picturerino\Entity\AspectRatio;

class LogUtility
{
    protected const TABLE_NAME = 'tx_picturerino_log';
    protected const SIMILAR_SIZE_RANGE = 20; // Pixelbereich für ähnliche Größen
    protected const STEP_SIZE = 50; // Pixelschritte für neue Größen

    public static function log(
        int $width,
        int $height,
        ?AspectRatio $aspectRatio,
        ?int $viewport = null
    ): void {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable(self::TABLE_NAME);

        $connection->insert(
            self::TABLE_NAME,
            [
                'width' => $width,
                'height' => $height,
                'ratio' => $aspectRatio?->getRatio(),
                'viewport' => $viewport,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
                'tstamp' => time(),
                'crdate' => time()
            ]
        );
    }

    public static function evaluate(
        int $width,
        int $height,
        ?AspectRatio $aspectRatio,
        ?int $viewport = null
    ): int
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable(self::TABLE_NAME);

        // Suche nach häufig verwendeten Größen im ähnlichen Bereich
        $qb = $connection->createQueryBuilder();
        $result = $qb
            ->select('width')
            ->addSelectLiteral('COUNT(*) as frequency')
            ->from(self::TABLE_NAME)
            ->where(
                $qb->expr()->and(
                    $qb->expr()->gte('width', $requestedWidth - self::SIMILAR_SIZE_RANGE),
                    $qb->expr()->lte('width', $requestedWidth + self::SIMILAR_SIZE_RANGE)
                )
            )
            ->andWhere(
                $qb->expr()->eq('ratio', $qb->createNamedParameter($aspectRatio?->getRatio() ?? ''))
            )
            ->groupBy('width')
            ->orderBy('frequency', 'DESC')
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();

        // Wenn eine häufig verwendete Größe gefunden wurde
        if ($result && $result['frequency'] > 0) {
            return (int)$result['width'];
        }

        // Ansonsten auf 50px-Raster runden
        return (int)(ceil($requestedWidth / self::STEP_SIZE) * self::STEP_SIZE);
    }
}
