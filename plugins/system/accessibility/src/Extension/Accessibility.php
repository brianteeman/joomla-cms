<?php

/**
 * @package     Joomla.Plugin
 * @subpackage  System.accessibility
 *
 * @copyright   (C) 2020 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Plugin\System\Accessibility\Extension;

use Joomla\CMS\Event\Application\BeforeCompileHeadEvent;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\SubscriberInterface;
use Joomla\CMS\Uri\Uri;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * System plugin to add additional accessibility features to the administrator interface.
 *
 * @since  4.0.0
 */
final class Accessibility extends CMSPlugin implements SubscriberInterface
{
    /**
     * Returns an array of events this subscriber will listen to.
     *
     * @return array
     *
     * @since   5.3.0
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'onBeforeCompileHead' => 'onBeforeCompileHead',
        ];
    }

    /**
     * Add the javascript for the accessibility menu
     *
     * @param  BeforeCompileHeadEvent $event  The event object
     *
     * @return  void
     *
     * @since   4.0.0
     */
    public function onBeforeCompileHead(BeforeCompileHeadEvent $event): void
    {
        $section = $this->params->get('section', 'administrator');
        $app     = $event->getApplication();

        if ($section !== 'both' && $app->isClient($section) !== true) {
            return;
        }

        // Get the document object.
        $document = $event->getDocument();

        if ($document->getType() !== 'html') {
            return;
        }

        // Are we in a modal?
        if ($app->getInput()->get('tmpl', '', 'cmd') === 'component') {
            return;
        }

        // Load language file.
        $this->loadLanguage();

        $language  = $app->getLanguage();

        // Determine if it is an LTR or RTL language
        $position = $language->isRtl() ? 'right' : 'left';

        // Change the position of the icon if it is set in the plugin parameters.
        $position = $this->params->get('icon_position', $position);

        // Detect the current active language
        $lang = $language->getTag();

        $iframeModals         = [];
        $accessibilityArticle = $this->params->get('accessibility_article');

        if (!empty($accessibilityArticle)) {
            $iframeModals[] = [
                'iframeUrl'  => Uri::root(true) . '/index.php?option=com_content&view=article&id=' . $accessibilityArticle . '&tmpl=component',
                'buttonText' => $this->getApplication()->getLanguage()->_('PLG_SYSTEM_ACCESSIBILITY_STATEMENT'),
            ];
        }

        $fontFaceSrc = [];
        $fontFamily = "";
        if ($this->params->get('useEmojis') === 'false'){
            $fontFaceSrc = [Uri::root(true) . '/media/plg_system_accessibility/icons/font/icons.css'];
            $fontFamily = "icons";
        }

        /**
        * Customisation and configuration options for the accessibility menu.
        * Reference  https://ranbuch.github.io/accessibility/
        */
        $document->addScriptOptions(
            'accessibility-options',
            [
                'labels' => [
                    'menuTitle'           => $language->_('PLG_SYSTEM_ACCESSIBILITY_MENU_TITLE'),
                    'increaseText'        => $language->_('PLG_SYSTEM_ACCESSIBILITY_INCREASE_TEXT'),
                    'decreaseText'        => $language->_('PLG_SYSTEM_ACCESSIBILITY_DECREASE_TEXT'),
                    'increaseTextSpacing' => $language->_('PLG_SYSTEM_ACCESSIBILITY_INCREASE_SPACING'),
                    'decreaseTextSpacing' => $language->_('PLG_SYSTEM_ACCESSIBILITY_DECREASE_SPACING'),
                    'invertColors'        => $language->_('PLG_SYSTEM_ACCESSIBILITY_INVERT_COLORS'),
                    'grayHues'            => $language->_('PLG_SYSTEM_ACCESSIBILITY_GREY'),
                    'underlineLinks'      => $language->_('PLG_SYSTEM_ACCESSIBILITY_UNDERLINE'),
                    'bigCursor'           => $language->_('PLG_SYSTEM_ACCESSIBILITY_CURSOR'),
                    'readingGuide'        => $language->_('PLG_SYSTEM_ACCESSIBILITY_READING'),
                    'textToSpeech'        => $language->_('PLG_SYSTEM_ACCESSIBILITY_TTS'),
                    'speechToText'        => $language->_('PLG_SYSTEM_ACCESSIBILITY_STT'),
                    'resetTitle'          => $language->_('PLG_SYSTEM_ACCESSIBILITY_RESET'),
                    'closeTitle'          => $language->_('PLG_SYSTEM_ACCESSIBILITY_CLOSE'),
                ],
                'icon' => [
                    'position' => [
                        $position => [
                            'size'  => '0',
                            'units' => 'px',
                        ],
                    ],
                    'useEmojis'  => $this->params->get('useEmojis') === 'true',
                    'fontFaceSrc'=> $fontFaceSrc,
                    'fontFamily' => $fontFamily,
                ],
                'hotkeys' => [
                    'enabled'    => true,
                    'helpTitles' => true,
                ],
                'language' => [
                    'textToSpeechLang' => $lang,
                    'speechToTextLang' => $lang,
                ],
                'iframeModals' => $iframeModals,
                /* disable the line height modules until/if they are fixed upstream
                * as they do not reset correctly
                */
                'modules' => [
                    'increaseLineHeight' => false,
                    'decreaseLineHeight' => false,
                ]
            ]
        );

        $document->getWebAssetManager()
            ->useScript('accessibility')
            ->registerAndUseStyle('accessibility', 'plg_system_accessibility/accessibility.css')
            ->addInlineScript(
                'window.addEventListener("load", function() {'
                . 'new Accessibility(Joomla.getOptions("accessibility-options") || {});'
                . '});',
                ['name' => 'inline.plg.system.accessibility'],
                ['type' => 'module'],
                ['accessibility']
            );
    }
}
