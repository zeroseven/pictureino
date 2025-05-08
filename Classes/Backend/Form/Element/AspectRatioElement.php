<?php

declare(strict_types=1);

namespace Zeroseven\Pictureino\Backend\Form\Element;

use TYPO3\CMS\Backend\Form\Element\AbstractFormElement;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder;
use Zeroseven\Pictureino\Utility\SettingsUtility;

class AspectRatioElement extends AbstractFormElement
{
    private const RENDER_TYPE = 'aspectRatio';
    private string $wrapperId = '';
    private string $fieldName = '';
    private array $breakpoints = [];
    private array $result = [];

    private function initializeElement(): void
    {
        $site = GeneralUtility::makeInstance(SiteFinder::class)->getSiteByPageId(1);

        $this->wrapperId = StringUtility::getUniqueId('id');
        $this->fieldName = StringUtility::getUniqueId('id');
        $this->breakpoints = GeneralUtility::makeInstance(SettingsUtility::class, $site)->getBreakpoints();
        $this->result = $this->initializeResultArray();
    }

    private function addStyles(): void
    {
        $this->result['stylesheetFiles'][] = 'EXT:pictureino/Resources/Public/Css/backend/element/aspectratio.css';
    }

    private function addJavaScript(): void
    {
        $parameterArray = $this->data['parameterArray'];
        $value = $parameterArray['itemFormElValue'] ?? '';

        $this->result['javaScriptModules'][] = JavaScriptModuleInstruction::create(
            '@zeroseven/pictureino/backend/element/aspectratio.js'
        )->instance($this->fieldName, $this->wrapperId, $value, json_encode(array_keys($this->breakpoints)));
    }

    private function addMarkup(): void
    {
        $wrap = GeneralUtility::makeInstance(TagBuilder::class, 'div');
        $wrap->addAttribute('id', $this->wrapperId);

        // die(debug($this->breakpoints));
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
            'data-type' => self::class,
        ]);

        $this->result['additionalHiddenFields'][] = $tag->render();
    }

    public function render(): array
    {
        $this->initializeElement();
        $this->addMarkup();
        $this->addJavaScript();
        $this->addHiddenField();
        $this->addStyles();

        return $this->result;
    }

    public static function register(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1746561328] = [
            'nodeName' => self::RENDER_TYPE,
            'class' => self::class,
            'priority' => 40,
        ];
    }

    public static function addTCAConfig(?array $override = null): array
    {
        return array_merge_recursive([
            'type' => 'user',
            'renderType' => self::RENDER_TYPE,
            'default' => '',
        ], $override ?? []);
    }
}
