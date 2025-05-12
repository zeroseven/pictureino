<?php

declare(strict_types=1);

namespace Zeroseven\Pictureino\ViewHelpers;

use TYPO3\CMS\Core\Page\AssetCollector;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use Zeroseven\Pictureino\Entity\ConfigRequest;
use Zeroseven\Pictureino\Utility\AspectRatioUtility;
use Zeroseven\Pictureino\Utility\ImageUtility;
use Zeroseven\Pictureino\Utility\TagUtility;

class ImageViewHelper extends AbstractViewHelper
{
    protected $escapeOutput = false;
    protected ImageUtility $imageUtiltiy;
    protected AspectRatioUtility $aspectRatioUtiltiy;
    protected AssetCollector $assetCollector;

    protected const FALLBACK_WIDTH = 80;
    protected const SEO_CONTENT_WIDTH = 1200;
    public const ON_LOAD_EVENT = 'Pictureiño.handle(this)';

    public function __construct()
    {
        $this->imageUtiltiy = GeneralUtility::makeInstance(ImageUtility::class);
        $this->aspectRatioUtiltiy = GeneralUtility::makeInstance(AspectRatioUtility::class);
    }

    public function injectAssetCollector(AssetCollector $assetCollector): void
    {
        $this->assetCollector = $assetCollector;
    }

    public function initializeArguments(): void
    {
        parent::initializeArguments();

        // Default image attrubutes
        $this->registerArgument('src', 'string', 'a path to a file, a combined FAL identifier or an uid (int). If $treatIdAsReference is set, the integer is considered the uid of the sys_file_reference record. If you already got a FAL object, consider using the $image parameter instead', false, '');
        $this->registerArgument('treatIdAsReference', 'bool', 'given src argument is a sys_file_reference record', false, false);
        $this->registerArgument('image', 'object', 'a FAL object (\\TYPO3\\CMS\\Core\\Resource\\File or \\TYPO3\\CMS\\Core\\Resource\\FileReference)');
        $this->registerArgument('cropVariant', 'string', 'select a cropping variant, in case multiple croppings have been specified or stored in FileReference', false, 'default');
        $this->registerArgument('fileExtension', 'string', 'Custom file extension to use');
        $this->registerArgument('width', 'string', 'width of the image. This can be a numeric value representing the fixed width of the image in pixels. But you can also perform simple calculations by adding "m" or "c" to the value. See imgResource.width in the TypoScript Reference on https://docs.typo3.org/permalink/t3tsref:confval-imgresource-width for possible options.');
        $this->registerArgument('height', 'string', 'height of the image. This can be a numeric value representing the fixed height of the image in pixels. But you can also perform simple calculations by adding "m" or "c" to the value. See imgResource.height in the TypoScript Reference https://docs.typo3.org/permalink/t3tsref:confval-imgresource-height for possible options.');

        // Image rendering settings
        $this->registerArgument('aspectRatio', 'string|array', 'Set the aspect ratio of the image, or create a array for different formats like "{xs:\'1:1\', sm:\'4:3\', lg:\'16:9\'}"');
        $this->registerArgument('freeAspectRatio', 'bool', 'Set free aspect ratio. This means that the image will be cropped to the given width and height. (This is the same than "aspectRatio=\'free\'")', false, false);
        $this->registerArgument('retina', 'bool', 'If set, the image will be rendered in retina mode. This means that the image will be rendered in double size and scaled down to the original size. This is useful for high-resolution displays.');

        // Some attributes for the image tag
        $this->registerArgument('alt', 'string', 'Specifies an alternate text for an image');
        $this->registerArgument('title', 'string', 'Specifies title for an image');
        $this->registerArgument('class', 'string', 'Classname');
        $this->registerArgument('style', 'string', 'Inline styles');
    }

    protected function createEncryptionHash(): string
    {
        $configRequest = GeneralUtility::makeInstance(ConfigRequest::class);
        $file = $this->imageUtiltiy->getFile();

        if ($file instanceof FileReference) {
            $configRequest->addConfig('file', [
                'src' => $file->getReferenceProperty('uid'),
                'treatIdAsReference' => true,
            ]);
        } elseif ($file instanceof File) {
            $configRequest->addConfig('file', [
                'src' => $file->getPublicUrl(),
                'treatIdAsReference' => false,
            ]);
        }

        if (null !== $this->arguments['retina']) {
            $configRequest->addConfig('retina', (bool) $this->arguments['retina']);
        }

        if (!$this->aspectRatioUtiltiy->isEmpty()) {
            $configRequest->addConfig('aspectRatio', $this->aspectRatioUtiltiy->toArray());
        }

        if ($cropVariant = $this->arguments['cropVariant'] ?? null) {
            $configRequest->addConfig('cropVariant', $cropVariant);
        }

        return $configRequest->encryptConfig();
    }

    protected function determineAspectRatio(): AspectRatioUtility
    {
        if ($this->arguments['freeAspectRatio'] ?? false) {
            return $this->aspectRatioUtiltiy->set('');
        }

        if ($aspectRatio = $this->arguments['aspectRatio']) {
            return $this->aspectRatioUtiltiy->set($aspectRatio);
        }

        if (($width = $this->arguments['width']) && $height = $this->arguments['height']) {
            return $this->aspectRatioUtiltiy->add([$width, $height], 0);
        }

        return $this->aspectRatioUtiltiy->add($this->imageUtiltiy->getFile(), 0);
    }

    protected function addInlineScript(): void
    {
        $script = <<< JS
        if (typeof Pictureiño === 'undefined') {
            Pictureiño = {
                handle: function (c) {
                    window.addEventListener('load', function () {
                        Pictureiño.handle(c);
                    });
                }
            }
        }
        JS;

        $this->assetCollector->addInlineJavaScript('pictureino-handle', $script, [], [
            'priority' => true,
            'useNonce' => true,
        ]);
    }

    /** @throws \Exception */
    public function render(): string
    {
        $this->imageUtiltiy->setFile(
            $this->arguments['src'],
            $this->arguments['image'],
            $this->arguments['treatIdAsReference'] ?? false
        );

        if ($cropVariant = $this->arguments['cropVariant']) {
            $this->imageUtiltiy->setCropVariant($cropVariant);
        }

        $this->determineAspectRatio();
        $this->addInlineScript();

        $tagUtility = GeneralUtility::makeInstance(TagUtility::class, $this->imageUtiltiy, $this->aspectRatioUtiltiy)
            ->addAttribute('data-config', $this->createEncryptionHash())
            ->addAttribute('data-loaded', 'false')
            ->addAttribute('title', $this->arguments['title'])
            ->addAttribute('alt', $this->arguments['alt'])
            ->addAttribute('class', $this->arguments['class'])
            ->addAttribute('style', $this->arguments['style'])
            ->addAttribute('onload', static::ON_LOAD_EVENT);

        return ($this->aspectRatioUtiltiy->count() <= 1
            ? $tagUtility->renderImg(static::FALLBACK_WIDTH)
            : $tagUtility->renderPicture(static::FALLBACK_WIDTH))
            . "\n" . $tagUtility->structuredData(static::SEO_CONTENT_WIDTH);
    }
}
