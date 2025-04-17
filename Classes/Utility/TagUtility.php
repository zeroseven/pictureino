<?php declare(strict_types=1);

namespace Zeroseven\Picturerino\Utility;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder;
use Zeroseven\Picturerino\Entity\AspectRatio;
use Zeroseven\Picturerino\Utility\ImageUtility;
use Zeroseven\Picturerino\Utility\AspectRatioUtility;

class TagUtility {
    protected ImageUtility $imageUtility;
    protected AspectRatioUtility $aspectRatioUtility;
    protected bool $debugMode;
    protected array $attributes = [];
    protected ?string $title = null;
    protected ?string $alt = null;
    protected ?string $class = null;

    public function __construct(ImageUtility $imageUtility, AspectRatioUtility $aspectRatioUtility)
    {
        $this->imageUtility = $imageUtility;
        $this->aspectRatioUtility = $aspectRatioUtility;
        $this->debugMode = (bool)GeneralUtility::makeInstance(SettingsUtility::class)->get('debug');
    }

    public function addAttribute(string $attribute, string $value = null): self
    {
        $value === null || $this->attributes[$attribute] = $value;

        return $this;
    }

    public function getAttribute(string $attribute): ?string
    {
        return $this->attributes[$attribute] ?? null;
    }

    public function getDataAttributes(): array
    {
        return array_filter($this->attributes, fn($key)  =>  str_starts_with($key, 'data-'), ARRAY_FILTER_USE_KEY);
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

        if ($this->debugMode) {
            $source->addAttribute('data-aspact-ratio', (string)$ratio);
        }

        if ($mimetype = $this->imageUtility->getProperty('mimetype')) {
            $source->addAttribute('type', $mimetype);
        }

        return $source->render();
    }

    public function renderImg(int $width = null): string
    {
        $firstAspect = $this->aspectRatioUtility->getFirstAspectRatio();
        $height = $width && $firstAspect ? $firstAspect->getHeight($width) : null;

        $this->imageUtility->processImage($width, $height);

        $img = GeneralUtility::makeInstance(TagBuilder::class, 'img');
        $img->addAttribute('src', $this->imageUtility->getUrl());
        $img->addAttribute('width', $this->imageUtility->getProperty('width'));
        $img->addAttribute('height', $this->imageUtility->getProperty('height'));
        $img->addAttribute('srcset', $this->imageUtility->getUrl($this->imageUtility->processImage($width * 3, $height * 3)). ' 3x');

        $this->getAttribute('title') || $this->addAttribute('title', $this->imageUtility->getProperty('title'));
        $this->getAttribute('alt') || $this->addAttribute('alt', $this->imageUtility->getProperty('alternative') ?? '');

        $this->debugMode && $this->addAttribute('data-aspect-ratio', (string)$firstAspect);

        foreach ($this->attributes as $key => $value) {
            $value === null || $img->addAttribute($key, $value);
        }

        return $img->render();
    }

    public function renderPicture(int $width = null): string
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

}
