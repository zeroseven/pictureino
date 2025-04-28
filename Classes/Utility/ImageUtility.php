<?php

declare(strict_types=1);

namespace Zeroseven\Picturerino\Utility;

use TYPO3\CMS\Core\Imaging\GraphicalFunctions;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Service\ImageService;

class ImageUtility
{
    protected ImageService $imageService;
    protected FileInterface $file;
    protected ?array $processedFiles;
    protected ?bool $webpSupported;

    public function __construct()
    {
        $this->imageService = GeneralUtility::makeInstance(ImageService::class);
    }

    protected function isWebpSupported(): bool
    {
        return $this->webpSupported ??= GeneralUtility::makeInstance(GraphicalFunctions::class)?->webpSupportAvailable()
            && strpos(GeneralUtility::getIndpEnv('HTTP_ACCEPT') ?? '', 'image/webp') !== false;
    }

    public function getFile(): FileInterface
    {
        return $this->file;
    }

    public function hasFile(): bool
    {
        return null !== $this->file;
    }

    /** @throws \Exception */
    public function setFile(?string $src = null, mixed $image = null, ?bool $treatIdAsReference = null): self
    {
        if (('' === $src && null === $image) || ('' !== $src && null !== $image)) {
            throw new \Exception('You must either specify a string src or a File object.', 1382284104);
        }

        if (($file = $this->imageService->getImage($src, $image, $treatIdAsReference)) instanceof FileInterface) {
            $this->file = $file;

            return $this;
        }

        throw new \Exception('Either file could not be found or the file is not an instance of FileInterface', 1382284103);
    }

    /** @throws \Exception */
    public function processImage(int|string|null $width = null, int|string|null $height = null, bool $forceWebp = null, ?array $processingInstructions = null): ProcessedFile
    {
        if (null === $this->file) {
            throw new \Exception('No image. Please call "setFile" method, to set an image file', 1382284105);
        }

        return $this->processedFiles[] = $this->imageService->applyProcessingInstructions($this->file, array_merge($processingInstructions ?? [], [
            'width' => $width ? ($width . 'c') : null,
            'height' => $height ? ($height . 'c') : null,
        ], $forceWebp && $this->isWebpSupported() ? [
            'fileExtension' => 'webp',
        ] : []));
    }

    /** @throws \Exception */
    public function getUrl(?ProcessedFile $processedFile = null): string
    {
        $processedFile ??= $this->getLastProcessedFile();

        if (null === $processedFile) {
            throw new \Exception('No processed file. Please call "processImage" method, to process an image file', 1382284106);
        }

        return $this->imageService->getImageUri($processedFile ?? $this->getLastProcessedFile(), true);
    }

    /** @throws \Exception */
    public function getProperty(string $property, ?ProcessedFile $processedFile = null): ?string
    {
        $processedFile ??= $this->getLastProcessedFile();

        if (null === $processedFile) {
            throw new \Exception('No processed file. Please call "processImage" method, to process an image file', 1382284107);
        }

        return $processedFile && $processedFile->hasProperty($property)
            ? (string) $processedFile->getProperty($property)
            : null;
    }

    public function getLastProcessedFile(): ?ProcessedFile
    {
        if (null === $this->processedFiles) {
            return null;
        }

        return end($this->processedFiles);
    }

    public function getProcessedFiles(): ?array
    {
        return $this->processedFiles;
    }
}
