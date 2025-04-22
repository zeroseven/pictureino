<?php declare(strict_types=1);

namespace Zeroseven\Picturerino\Utility;

use Exception;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Service\ImageService;


class ImageUtility
{
    protected ImageService $imageService;
    protected FileInterface $file;
    protected ?array $processedFiles = [];

    public function __construct()
    {
        $this->imageService = GeneralUtility::makeInstance(ImageService::class);
    }

    public function getFile(): FileInterface
    {
        return $this->file;
    }

    public function hasFile(): bool
    {
        return $this->file !== null;
    }

    /** @throws Exception */
    public function setFile(string $src = null, mixed $image = null, bool $treatIdAsReference = null): self
    {
        if (($src === '' && $image === null) || ($src !== '' && $image !== null)) {
            throw new Exception('You must either specify a string src or a File object.', 1382284104);
        }

        if(($file = $this->imageService->getImage($src, $image, $treatIdAsReference)) instanceof FileInterface) {
            $this->file = $file;

            return $this;
        }

        throw new Exception('Either file could not be found or the file is not an instance of FileInterface', 1382284103);
    }

    /** @throws Exception */
    public function processImage(int|string $width = null, int|string $height = null, bool $keepAspectRatio = null, array $processingInstructions = null): ProcessedFile
    {
        if ($this->file === null) {
            throw new Exception('No image. Please call "setFile" method, to set an image file', 1382284105);
        }

        $mode = !$keepAspectRatio && $width && $height ? 'c' : 'm';

        return $this->processedFiles[] = $this->imageService->applyProcessingInstructions($this->file, array_merge($processingInstructions ?? [], [
            'width' => $width ? ($width . $mode) : null,
            'height' => $height ? ($height . $mode) : null
        ]));
    }

    /** @throws Exception */
    public function getUrl(ProcessedFile $processedFile = null): string
    {
        $processedFile ??= $this->getLastProcessedFile();

        if ($processedFile === null) {
            throw new Exception('No processed file. Please call "processImage" method, to process an image file', 1382284106);
        }

        return $this->imageService->getImageUri($processedFile ?? $this->getLastProcessedFile(), true);
    }

    /** @throws Exception */
    public function getProperty(string $property, ProcessedFile $processedFile = null): ?string
    {
        $processedFile ??= $this->getLastProcessedFile();

        if ($processedFile === null) {
            throw new Exception('No processed file. Please call "processImage" method, to process an image file', 1382284107);
        }

        return $processedFile && $processedFile->hasProperty($property)
            ? (string)$processedFile->getProperty($property)
            : null;
    }

    public function getLastProcessedFile(): ?ProcessedFile
    {
        return end($this->processedFiles) ?: null;
    }
}
