<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  System.skipto
 *
 * @copyright   Copyright (C) 2005 - 2019 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;

/**
 * Skipto plugin to add accessible keyboard navigation to the administrator template.
 *
 * @since  __DEPLOY_VERSION__
 */
class PlgSystemSkipto extends CMSPlugin
{
	/**
	 * If true, language files will be loaded automatically.
	 *
	 * @var    boolean
	 * @since  4.0.0
	 */
	protected $autoloadLanguage = true;

	/**
	 * Application object.
	 *
	 * @var    JApplicationCms
	 * @since  4.0.0
	 */
	protected $app;

	/**
	 * Add the css and javascript for the skipto navigation menu
	 *
	 * @return  void
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function onBeforeCompileHead()
	{
		$section         = (int) $this->params->get('section_skipto', 2);
		$current_section = 0;

		// Get the document object.
		$document = Factory::getDocument();

		try
		{
			$app = Factory::getApplication();

			if ($app->isClient('administrator'))
			{
				$current_section = 2;
			}
			elseif ($app->isClient('site'))
			{
				$current_section = 1;
			}
		}
		catch (Exception $exc)
		{
			$current_section = 0;
		}

		if (!($current_section & $section))
		{
			return false;
		}

		{
		// Add strings for translations in Javascript.
		$this->loadLanguage();
		Factory::getDocument()->addScriptOptions(
			'skipto-settings',
				[
					'settings' => [
						'skipTo' => [
							'buttonLabel'		=> Text::_('PLG_SYSTEM_SKIPTO_SKIP_TO'),
							'buttonDivTitle' 	=> Text::_('PLG_SYSTEM_SKIPTO_SKIP_TO_KEYBOARD'),
							'menuLabel'		=> Text::_('PLG_SYSTEM_SKIPTO_SKIP_TO_AND_PAGE_OUTLINE'),
							'landmarksLabel'	=> Text::_('PLG_SYSTEM_SKIPTO_SKIP_TO'),
							'headingsLabel'		=> Text::_('PLG_SYSTEM_SKIPTO_PAGE_OUTLINE'),
							'contentLabel'		=> Text::_('PLG_SYSTEM_SKIPTO_CONTENT'),
						]
					]
				]
		);
		HTMLHelper::_('script', 'vendor/skipto/dropMenu.js', ['version' => 'auto', 'relative' => true], ['defer' => true]);
		HTMLHelper::_('script', 'vendor/skipto/skipTo.js', ['version' => 'auto', 'relative' => true], ['defer' => true]);
		HTMLHelper::_('stylesheet', 'vendor/skipto/SkipTo.css', ['version' => 'auto', 'relative' => true]);

		$document->addScriptDeclaration("document.addEventListener('DOMContentLoaded', function() {
			window.SkipToConfig = Joomla.getOptions('skipto-settings');
			window.skipToMenuInit();
		});");
		}
	}
}
