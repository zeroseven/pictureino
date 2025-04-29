<?php

declare(strict_types=1);

namespace Zeroseven\Pictureino\Utility;

use TYPO3\CMS\Core\Imaging\GraphicalFunctions;
use TYPO3\CMS\Core\Imaging\ImageManipulation\Area;
use TYPO3\CMS\Core\Imaging\ImageManipulation\CropVariantCollection;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Service\ImageService;

class ImageUtility
{
    public const string DEFAULT_CROP_VARIANT = 'picutureino';

    protected ImageService $imageService;
    protected FileInterface $file;
    protected ?array $processedFiles;
    protected ?bool $webpSupported;
    protected ?CropVariantCollection $cropVariantCollection;
    protected ?string $cropVariant;

    public function __construct()
    {
        $this->imageService = GeneralUtility::makeInstance(ImageService::class);
    }

    protected function isWebpSupported(): bool
    {
        return $this->webpSupported ??= GeneralUtility::makeInstance(GraphicalFunctions::class)?->webpSupportAvailable();
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

            if ($file instanceof FileReference && $file->hasProperty('crop')) {
                $this->cropVariantCollection = CropVariantCollection::create($file->getProperty('crop'));
            }

            return $this;
        }

        throw new \Exception('Either file could not be found or the file is not an instance of FileInterface', 1382284103);
    }

    public function getCropArea(): ?Area
    {
        if ($area = $this->cropVariantCollection?->getCropArea($this->getCropVariant())) {
            return $area->isEmpty() ? null : $area;
        }

        return null;
    }

    public function getFocusArea(): ?Area
    {
        if ($area = $this->cropVariantCollection?->getFocusArea($this->getCropVariant())) {
            return $area->isEmpty() ? null : $area;
        }

        return null;
    }

    public function setCropVariant(string $cropVariant): self
    {
        $this->cropVariant = $cropVariant;

        return $this;
    }

    public function getCropVariant(): string
    {
        return $this->cropVariant ?? self::DEFAULT_CROP_VARIANT;
    }

    protected function calculateFocus(float $offset, float $size): int
    {
        if ($size >= 1.0) {
            return 0;
        }

        $focus = ($offset / (1.0 - $size)) * 200 - 100;

        return max(-100, min(100, (int)round($focus)));
    }

    /** @throws \Exception */
    public function processImage(int|string|null $width = null, int|string|null $height = null, bool $forceWebp = null, ?array $processingInstructions = []): ProcessedFile
    {
        if (null === $this->file) {
            throw new \Exception('No image. Please call "setFile" method, to set an image file', 1382284105);
        }

        if ($forceWebp && $this->isWebpSupported()) {
            $processingInstructions['fileExtension'] = 'webp';
        }

        if ($width) {
            $processingInstructions['width'] = $width . 'c';
        }

        if ($height) {
            $processingInstructions['height'] = $height . 'c';
        }

        if ($cropArea = $this->getCropArea()) {
            $processingInstructions['crop'] = $cropArea->makeAbsoluteBasedOnFile($this->file);
        }

        if ($focusArea = $this->getFocusArea()) {
            $processingInstructions['width'] = $width . 'c' . $this->calculateFocus($focusArea->getOffsetLeft(), $focusArea->getWidth());
            $processingInstructions['height'] = $height . 'c' . $this->calculateFocus($focusArea->getOffsetTop(), $focusArea->getHeight());
        }

        return $this->processedFiles[] = $this->imageService->applyProcessingInstructions($this->file, $processingInstructions);
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
