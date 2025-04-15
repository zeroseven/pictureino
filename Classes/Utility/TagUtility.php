<?php declare(strict_types=1);

namespace Zeroseven\Picturerino\Utility;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder;
use Zeroseven\Picturerino\Utility\ImageUtility;
use Zeroseven\Picturerino\Utility\AspectRatioUtility;

class TagUtility {
    protected ImageUtility $imageUtility;
    protected AspectRatioUtility $aspectRatioUtility;

    protected ?string $title = null;
    protected ?string $alt = null;
    protected ?string $class = null;

    public function __construct(ImageUtility $imageUtility, AspectRatioUtility $aspectRatioUtility)
    {
        $this->imageUtility = $imageUtility;
        $this->aspectRatioUtility = $aspectRatioUtility;
    }

    public function setTitle(string $title = null): self
    {
        $this->title = $title;

        return $this;
    }

    public function setAlt(string $alt = null): self
    {
        $this->alt = $alt;

        return $this;
    }

    public function setClass(string $class = null): self
    {
        $this->class = $class;

        return $this;
    }

    protected function renderSource(int $breakpoint, array $ratio): string
    {
        $width = static::FALLBACK_WIDTH;
        $height = ($firstAspect = $this->aspectRatioUtility->getFirstAspectRatio()) ? $firstAspect->getHeight($width) : null;

        $this->imageUtility->processImage($width, $height);

        $source = GeneralUtility::makeInstance(TagBuilder::class, 'source');
        $source->addAttribute('media', '(min-width: ' . $breakpoint . 'px)');
        $source->addAttribute('srcset', $this->imageUtility->getUrl());

        if ($mimetype = $this->imageUtility->getProperty('mimetype')) {
            $source->addAttribute('type', $mimetype);
        }

        return $source->render();
    }

    public function renderImg(): string
    {
        $width = static::FALLBACK_WIDTH;
        $height = ($firstAspect = $this->aspectRatioUtility->getFirstAspectRatio()) ? $firstAspect->getHeight($width) : null;
        $alt = $this->alt ?: $this->imageUtility->getProptery('alternative') ?: '';

        $this->imageUtility->processImage($width, $height);

        $tag = GeneralUtility::makeInstance(TagBuilder::class, 'img');
        $tag->addAttribute('src', $this->imageUtility->getUrl());
        $tag->addAttribute('width', $this->imageUtility->getProperty('width'));
        $tag->addAttribute('height', $this->imageUtility->getProperty('height'));
        $tag->addAttribute('srcset', $this->imageUtility->getUrl($this->imageUtility->processImage($width * 3, $height * 3)). ' 3x');
        $tag->addAttribute('alt', $alt);

        if ($title = $this->title ?: $this->imageUtility->getProptery('title')) {
            $tag->addAttribute('title', $title);
        }

        if ($this->class) {
            $tag->addAttribute('class', $this->class);
        }

        return $tag->render();
    }

    public function renderPicture(): string
    {
        $tag = GeneralUtility::makeInstance(TagBuilder::class, 'picture');
        $children = [];

        foreach ($this->aspectRatioUtility->getAspectRatios() as $breakpoint => $ratio) {
            $breakpoint > 0 && ($children[] = $this->renderSource($breakpoint, $ratio));
        }

        $children[] = $this->renderImg();

        $tag->setContent(implode('', $children));

        return $tag->render();
    }

}
