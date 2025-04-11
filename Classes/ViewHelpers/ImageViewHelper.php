<?php

declare(strict_types=1);

namespace Zeroseven\Picturerino\ViewHelpers;

use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;
use Zeroseven\Picturerino\Utility\ImageUtility;

class ImageViewHelper extends AbstractTagBasedViewHelper
{
    protected ImageUtility $imageUtiltiy;
    protected ?FileInterface $image = null;

    public function __construct()
    {
        parent::__construct();

        $this->imageUtiltiy = GeneralUtility::makeInstance(ImageUtility::class);
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

        // Ratio
        $this->registerArgument('aspectRatio', 'string|array', 'Set the aspect ratio of the image, or create a array for different formats like "{xs:\'1:1\', sm:\'4:3\', lg:\'16:9\'}"');

        // Some attributes for the image tag
        $this->registerArgument('alt', 'string', 'Specifies an alternate text for an image');
        $this->registerArgument('title', 'string', 'Specifies title for an image');
        $this->registerArgument('class', 'string', 'Classname');
        $this->registerArgument('style', 'string', 'Inline styles');
    }

    protected function renderPicture(): string

    protected function renderImg(): string

    public function render(): string
    {
        $this->image = $this->imageUtiltiy->getImage(
            $this->arguments['src'],
            $this->arguments['image'],
            $this->arguments['treatIdAsReference'] ?? false
        );

        if ($this->image) {

        }
    }
}
