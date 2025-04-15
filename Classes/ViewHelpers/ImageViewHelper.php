<?php

declare(strict_types=1);

namespace Zeroseven\Picturerino\ViewHelpers;

use Exception;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use Zeroseven\Picturerino\Utility\ImageUtility;
use Zeroseven\Picturerino\Utility\AspectRatioUtility;
use Zeroseven\Picturerino\Utility\TagUtility;

class ImageViewHelper extends AbstractViewHelper
{
    protected ImageUtility $imageUtiltiy;
    protected AspectRatioUtility $aspectRatioUtiltiy;

    protected const FALLBACK_WIDTH = 200;

    public function __construct()
    {
        $this->imageUtiltiy = GeneralUtility::makeInstance(ImageUtility::class);
        $this->aspectRatioUtiltiy = GeneralUtility::makeInstance(AspectRatioUtility::class);
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

        // Ratio
        $this->registerArgument('aspectRatio', 'string|array', 'Set the aspect ratio of the image, or create a array for different formats like "{xs:\'1:1\', sm:\'4:3\', lg:\'16:9\'}"');

        // Some attributes for the image tag
        $this->registerArgument('alt', 'string', 'Specifies an alternate text for an image');
        $this->registerArgument('title', 'string', 'Specifies title for an image');
        $this->registerArgument('class', 'string', 'Classname');
        $this->registerArgument('style', 'string', 'Inline styles');
    }

    /** @throws Exception */
    public function render(): string
    {
        $this->imageUtiltiy->setFile(
            $this->arguments['src'],
            $this->arguments['image'],
            $this->arguments['treatIdAsReference'] ?? false
        );

        if ($aspectRatio = $this->arguments['aspectRatio']) {
            $this->aspectRatioUtiltiy->setAspectRatios($aspectRatio);
        } elseif ($width = $this->arguments['width'] && $height = $this->arguments['height']) {
            $this->aspectRatioUtiltiy->addAspectRatio([$width, $height], 0);
        }

        $tagUtility = GeneralUtility::makeInstance(TagUtility::class, $this->imageUtiltiy, $this->aspectRatioUtiltiy)
            ->setTitle($this->arguments['title'])
            ->setAlt($this->arguments['alt'])
            ->setClass($this->arguments['class']);

        return $this->aspectRatioUtiltiy->isEmpty()
            ? $tagUtility->renderImg()
            : $tagUtility->renderPicture();
    }
}
