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
    protected array $dataAttributes = [];
    protected ?string $title = null;
    protected ?string $alt = null;
    protected ?string $class = null;

    public function __construct(ImageUtility $imageUtility, AspectRatioUtility $aspectRatioUtility)
    {
        $this->imageUtility = $imageUtility;
        $this->aspectRatioUtility = $aspectRatioUtility;
        $this->debugMode = (bool)GeneralUtility::makeInstance(SettingsUtility::class)->get('debug');
    }

    public function addDataAttribute(string $attribute, string $value): self
    {
        $this->dataAttributes[$attribute] = $value;

        return $this;
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
        $img->addAttribute('alt',  $this->alt ?: ($this->imageUtility->getProperty('alternative') ?? ''));
        $img->addAttribute('onload', 'Picturerino.handle(this)');

        if ($this->debugMode) {
            $img->addAttribute('data-aspact-ratio', (string)$firstAspect);
        }

        if ($title = $this->title ?: $this->imageUtility->getProperty('title')) {
            $img->addAttribute('title', $title);
        }

        if ($this->class) {
            $img->addAttribute('class', $this->class);
        }

        if ($this->dataAttributes) {
            foreach ($this->dataAttributes as $key => $value) {
                $img->addAttribute('data-' . $key, $value);
            }
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
