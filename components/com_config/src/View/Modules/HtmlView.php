<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_config
 *
 * @copyright   (C) 2017 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Config\Site\View\Modules;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Language\Text;

/**
 * View to edit a module.
 *
 * @package     Joomla.Site
 * @subpackage  com_config
 * @since       3.2
 */
class HtmlView extends BaseHtmlView
{
	/**
	 * The module to be rendered
	 *
	 * @var   array
	 * @since 3.2
	 */
	public $item;

	/**
	 * The form object
	 *
	 * @var   \JForm
	 * @since 3.2
	 */
	public $form;

	/**
	 * Execute and display a template script.
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  mixed  A string if successful, otherwise an Error object.
	 *
	 * @since   3.2
	 */
	public function display($tpl = null)
	{
		$lang = Factory::getApplication()->getLanguage();
		$lang->load('', JPATH_ADMINISTRATOR, $lang->getTag());
		$lang->load('com_modules', JPATH_ADMINISTRATOR, $lang->getTag());

		// TODO Move and clean up
		$module = (new \Joomla\Component\Modules\Administrator\Model\ModuleModel)->getItem(Factory::getApplication()->input->getInt('id'));

		$moduleData = $module->getProperties();
		unset($moduleData['xml']);

		/** @var Modules $model */
		$model = $this->getModel();

		// Need to add module name to the state of model
		$model->getState()->set('module.name', $moduleData['module']);

		/** @var \JForm form */
		$this->form      = $this->get('form');
		$this->positions = $this->get('positions');
		$this->item      = $moduleData;

		if ($this->form)
		{
			$this->form->bind($moduleData);
		}

		$this->_prepareDocument();
		parent::display($tpl);
	}

	/**
	 * Prepares the document.
	 *
	 * @return  void
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	protected function _prepareDocument()
	{
		$app    = Factory::getApplication();

		// There is no menu item for this so we have to use the title from the component
		if ($app->get('sitename_pagetitles', 0) == 1)
		{
			$title = Text::sprintf('JPAGETITLE', $app->get('sitename'), Text::_('COM_CONFIG_MODULES_SETTINGS_TITLE'));
		}
		elseif ($app->get('sitename_pagetitles', 0) == 2)
		{
			$title = Text::sprintf('JPAGETITLE', Text::_('COM_CONFIG_MODULES_SETTINGS_TITLE'), $app->get('sitename'));
		}

		$this->document->setTitle($title);
	}
}
