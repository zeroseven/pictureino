<?php

declare(strict_types=1);

namespace Zeroseven\Picturerino\Utility;

use TYPO3\CMS\Core\Utility\GeneralUtility;

class AspectRatioUtility
{
    protected const BREAKPOINTS = [
        'xs' => 0,
        'sm' => 576,
        'md' => 768,
        'lg' => 992,
        'xl' => 1200,
        'xxl' => 1400
    ];

    /**
     * Validiert das Format eines Seitenverhältnisses (z.B. "16:9")
     */
    public static function isValidRatio(string $ratio): bool
    {
        return (bool)preg_match('/^\d+:\d+$/', $ratio);
    }

    /**
     * Konvertiert ein Seitenverhältnis-String in ein Array [x, y]
     */
    public static function splitRatio(string $ratio): ?array
    {
        if (self::isValidRatio($ratio)) {
            return array_slice(GeneralUtility::intExplode(':', $ratio, false), 0, 2);
        }

        return null;
    }

    /**
     * Verarbeitet Seitenverhältnisse und gibt ein Array mit Breakpoints zurück
     *
     * @param string|array $ratios Entweder ein String "16:9" oder ein Array ['xs' => '2:1', 'md' => '1:1']
     * @return array Array mit Breakpoints und x/y Werten
     */
    public static function processRatios($ratios): array
    {
        $result = [];

        // Initialisiere mit Null-Werten bei 0
        $result[0] = ['x' => null, 'y' => null];

        // Wenn ein einfacher String übergeben wurde
        if (is_string($ratios)) {
            if ($ratio = self::splitRatio($ratios)) {
                $result[0] = [
                    'x' => $ratio[0],
                    'y' => $ratio[1]
                ];
            }
            return $result;
        }

        // Wenn ein Array mit Breakpoints übergeben wurde
        if (is_array($ratios)) {
            $lastRatio = null;

            // Sortiere die Breakpoints
            $breakpoints = array_keys($ratios);
            usort($breakpoints, function($a, $b) {
                return (self::BREAKPOINTS[$a] ?? 0) <=> (self::BREAKPOINTS[$b] ?? 0);
            });

            foreach ($breakpoints as $breakpoint) {
                $pixelValue = self::BREAKPOINTS[$breakpoint] ?? 0;

                if (isset($ratios[$breakpoint]) && $ratio = self::splitRatio($ratios[$breakpoint])) {
                    $result[$pixelValue] = [
                        'x' => $ratio[0],
                        'y' => $ratio[1]
                    ];
                    $lastRatio = $ratio;
                }
            }

            // Wenn der erste Breakpoint nicht bei 0 startet, füge Null-Werte ein
            if (!isset($result[0])) {
                $result[0] = ['x' => null, 'y' => null];
            }

            // Sortiere nach Breakpoint-Werten
            ksort($result);
        }

        return $result;
    }

    /**
     * Berechnet die Höhe basierend auf der Breite und dem Seitenverhältnis
     */
    public static function calculateHeight(int $width, array $ratio): ?int
    {
        if (isset($ratio['x'], $ratio['y']) && $ratio['x'] !== null && $ratio['y'] !== null) {
            return (int)floor($width / $ratio['x'] * $ratio['y']);
        }

        return null;
    }

    /**
     * Berechnet die Breite basierend auf der Höhe und dem Seitenverhältnis
     */
    public static function calculateWidth(int $height, array $ratio): ?int
    {
        if (isset($ratio['x'], $ratio['y']) && $ratio['x'] !== null && $ratio['y'] !== null) {
            return (int)floor($height * $ratio['x'] / $ratio['y']);
        }

        return null;
    }
}
