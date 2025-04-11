<?php declare(strict_types=1);

namespace Zeroseven\Picturerino\Utility;

use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Service\ImageService;


class ImageUtility
{
    protected ImageService $imageService;

    public function __construct()
    {
        $this->imageService = GeneralUtility::makeInstance(ImageService::class);
    }

    public function getImage(string $src = null, mixed $image = null, bool $treatIdAsReference = null): FileInterface
    {
        if (($src === null && $image === null) || ($src !== null && $image !== null)) {
            throw new \Exception('You must either specify a string src or a File object.', 1382284106);
        }

        return $this->imageService->getImage($src, $image, $treatIdAsReference);
    }

    public function processImage(FileInterface $image, int|string $width = null, int|string $height = null, array $processingInstructions = null): ProcessedFile
    {
        return $this->imageService->applyProcessingInstructions($image, array_merge($processingInstructions ?? [], [
            'width' => $width,
            'height' => $height,
        ]));
    }

    public function getUrl(ProcessedFile $image): string
    {
        return $this->imageService->getImageUri($image, true);
    }

    public function getProptery(ProcessedFile $image, string $property): ?string
    {
        return $image->hasProperty($property)
            ? $image->getProperty($property)
            : null;
    }
}
