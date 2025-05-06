<?php

declare(strict_types=1);

namespace Zeroseven\Pictureino\Backend\Form\Element;

use TYPO3\CMS\Backend\Form\Element\AbstractFormElement;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder;

class AspectRatio extends AbstractFormElement
{
    private string $wrapperId = '';
    private string $fieldName = '';
    private array $result = [];

    private function initializeElement(): void
    {
        $this->wrapperId = StringUtility::getUniqueId('id');
        $this->fieldName = StringUtility::getUniqueId('id');
        $this->result = $this->initializeResultArray();
    }

    private function addJavaScript(): void
    {
        $parameterArray = $this->data['parameterArray'];
        $parameters = $parameterArray['fieldConf']['config']['parameters'] ?? [];

        $this->result['javaScriptModules'][] = JavaScriptModuleInstruction::create(
            '@zeroseven/pictureino/Backend/aspectratio.js'
        )->instance($this->fieldName, $this->wrapperId, json_encode($parameters));
    }

    private function addMarkup(): void
    {
        $wrap = GeneralUtility::makeInstance(TagBuilder::class, 'div');
        $wrap->addAttribute('id', $this->wrapperId);

        $this->result['html'] .= $wrap->render();
    }

    private function addHiddenField(): void
    {
        $parameterArray = $this->data['parameterArray'];

        $tag = GeneralUtility::makeInstance(TagBuilder::class, 'input');
        $tag->forceClosingTag(true);
        $tag->addAttributes([
            'type' => 'hidden',
            'id' => $this->fieldName,
            'name' => $parameterArray['itemFormElName'],
            'value' => htmlspecialchars($parameterArray['itemFormElValue']),
            'data-type' => self::class
        ]);

        $this->result['additionalHiddenFields'][] = $tag->render();
    }

    public function render(): array
    {
        $this->initializeElement();
        $this->addMarkup();
        $this->addJavaScript();
        $this->addHiddenField();

        return $this->result;
    }

    public static function register(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1746561328] = [
            'nodeName' => 'aspectRatio',
            'priority' => 40,
            'class' => static::class,
        ];
    }
}
