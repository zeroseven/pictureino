<?php

declare(strict_types=1);

namespace Zeroseven\Pictureino\Utility;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder;
use Zeroseven\Pictureino\Entity\AspectRatio;

class TagUtility
{
    protected string $config;
    protected ImageUtility $imageUtility;
    protected AspectRatioUtility $aspectRatioUtility;
    protected array $attributes = [];
    protected ?string $title = null;
    protected ?string $alt = null;
    protected ?string $class = null;

    public function __construct(string $config, ImageUtility $imageUtility, AspectRatioUtility $aspectRatioUtility)
    {
        $this->config = $config;
        $this->imageUtility = $imageUtility;
        $this->aspectRatioUtility = $aspectRatioUtility;
    }

    public function addAttribute(string $attribute, ?string $value = null): self
    {
        null === $value || $this->attributes[$attribute] = $value;

        return $this;
    }

    public function getAttribute(string $attribute): ?string
    {
        return $this->attributes[$attribute] ?? null;
    }

    public function getDataAttributes(): array
    {
        return array_filter($this->attributes, fn ($key) => str_starts_with($key, 'data-'), ARRAY_FILTER_USE_KEY);
    }

    protected function renderSource(int $breakpoint, AspectRatio $ratio, int $width): string
    {
        $height = $width ? $ratio->getHeight($width) : null;

        $this->imageUtility->processImage($width, $height);

        $source = GeneralUtility::makeInstance(TagBuilder::class, 'source');
        $source->addAttribute('media', '(min-width: ' . $breakpoint . 'px)');
        $source->addAttribute('srcset', $this->imageUtility->getUrl());
        $source->addAttribute('width', $this->imageUtility->getProperty('width'));
        $source->addAttribute('height', $this->imageUtility->getProperty('height'));

        if ($mimetype = $this->imageUtility->getProperty('mimetype')) {
            $source->addAttribute('type', $mimetype);
        }

        return $source->render();
    }

    public function renderImg(int $width): string
    {
        $firstAspect = $this->aspectRatioUtility->getFirstAspectRatio();
        $height = $width && $firstAspect ? $firstAspect->getHeight($width) : null;

        $this->imageUtility->processImage($width, $height);

        $img = GeneralUtility::makeInstance(TagBuilder::class, 'img');
        $img->addAttribute('src', $this->imageUtility->getUrl());
        $img->addAttribute('width', $this->imageUtility->getProperty('width'));
        $img->addAttribute('height', $this->imageUtility->getProperty('height'));

        $this->getAttribute('title') || $this->addAttribute('title', $this->imageUtility->getProperty('title'));
        $this->getAttribute('alt') || $this->addAttribute('alt', $this->imageUtility->getProperty('alternative') ?? '');

        foreach ($this->attributes as $key => $value) {
            null === $value || $img->addAttribute($key, $value);
        }

        return $img->render();
    }

    public function renderPicture(int $width): string
    {
        $tag = GeneralUtility::makeInstance(TagBuilder::class, 'picture');
        $aspectRatios = $this->aspectRatioUtility->getAspectRatios();
        $children = [];

        krsort($aspectRatios);

        foreach ($aspectRatios as $breakpoint => $ratio) {
            $breakpoint > 0 && ($children[] = $this->renderSource($breakpoint, $ratio, $width));
        }

        $children[] = $this->renderImg($width);

        $tag->setContent(implode("\n", $children));

        return $tag->render();
    }

    public function renderWrap(string $content): string
    {
        $tag = GeneralUtility::makeInstance(TagBuilder::class, 'pictureino-wrap');
        $tag->setContent($content);
        $tag->addAttribute('data-loading', null);
        $tag->addAttribute('data-config', $this->config);

        return $tag->render();
    }

    public function structuredData(int $width): string
    {
        $processedFile = $this->imageUtility->processImage($width, null, true);

        $script = GeneralUtility::makeInstance(TagBuilder::class, 'script');
        $script->addAttribute('type', 'application/ld+json');
        $script->setContent(json_encode(array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'ImageObject',
            'contentUrl' => $this->imageUtility->getUrl(),
            'width' => $this->imageUtility->getProperty('width'),
            'height' => $this->imageUtility->getProperty('height'),
            'caption' => $this->getAttribute('title') ?? $this->getAttribute('alt') ?? $this->imageUtility->getProperty('title') ?? $this->imageUtility->getProperty('alternative'),
            'encodingFormat' => $processedFile->getMimeType(),
        ])));

        return $script->render();
    }
}
