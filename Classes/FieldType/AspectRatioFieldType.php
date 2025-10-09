<?php

declare(strict_types=1);

namespace Zeroseven\Pictureino\FieldType;

use TYPO3\CMS\ContentBlocks\FieldType\AbstractFieldType;
use TYPO3\CMS\ContentBlocks\FieldType\FieldType;
use TYPO3\CMS\ContentBlocks\FieldType\WithCommonProperties;
use Zeroseven\Pictureino\Backend\Form\Element\AspectRatioElement;

#[FieldType(name: 'AspectRatio', tcaType: 'user', searchable: false)]
final class AspectRatioFieldType extends AbstractFieldType
{
    use WithCommonProperties;

    /**
     * @param array<string,mixed> $settings
     * @return $this
     */
    public function createFromArray(array $settings): self
    {
        // Clone the service instance, so that state for name, tcaType and searchable is carried over.
        $self = clone $this;
        $self->setCommonProperties($settings);
        return $self;
    }

    /**
     * @return array<string,mixed>
     */
    public function getTca(): array
    {
        $tca = $this->toTca();
        $tca['config'] = AspectRatioElement::addTCAConfig();
        return $tca;
    }

    public function getSql(string $column): string
    {
        return "`$column` VARCHAR(255) DEFAULT '' NOT NULL";
    }
}
