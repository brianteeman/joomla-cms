<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Installer.webinstaller
 *
 * @copyright   Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Rule\UrlRule;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Installer\Installer;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Updater\Update;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Version;

/**
 * Support for the "Install from Web" tab
 *
 * @since  3.2
 */
class PlgInstallerWebinstaller extends CMSPlugin
{
	const REMOTE_URL = 'https://appscdn.joomla.org/webapps/';

	/**
	 * The application object.
	 *
	 * @var    CMSApplication
	 * @since  __DEPLOY_VERSION__
	 */
	protected $app;

	/**
	 * The URL to install from
	 *
	 * @var    string|null
	 * @since  __DEPLOY_VERSION__
	 */
	private $installfrom = null;

	/**
	 * Flag if the document is in a RTL direction
	 *
	 * @var    integer|null
	 * @since  __DEPLOY_VERSION__
	 */
	private $rtl = null;

	public function onInstallerBeforeDisplay(&$showJedAndWebInstaller)
	{
		$showJedAndWebInstaller = false;
	}

	public function onInstallerViewBeforeFirstTab()
	{
		Factory::getLanguage()->load('plg_installer_webinstaller', JPATH_ADMINISTRATOR);

		if (!$this->params->get('tab_position', 0))
		{
			$this->getChanges();
		}
	}

	public function onInstallerViewAfterLastTab()
	{
		if ($this->params->get('tab_position', 0))
		{
			$this->getChanges();
		}

		$installfrom   = $this->getInstallFrom();

		HTMLHelper::_('script', 'plg_installer_webinstaller/client.min.js', ['version' => 'auto', 'relative' => true]);
		HTMLHelper::_('stylesheet', 'plg_installer_webinstaller/client.min.css', ['version' => 'auto', 'relative' => true]);

		$manifest = (new Installer)->isManifest(__DIR__ . '/webinstaller.xml');

		$devLevel = Version::PATCH_VERSION;

		if (!empty(Version::EXTRA_VERSION))
		{
			$devLevel .= '-' . Version::EXTRA_VERSION;
		}

		$doc = Factory::getDocument();

		$doc->addScriptOptions(
			'plg_installer_webinstaller',
			[
				'base_url'        => addslashes(self::REMOTE_URL),
				'installat_url'   => base64_encode(Uri::current() . '?option=com_installer&view=install'),
				'installfrom_url' => addslashes($installfrom),
				'product'         => base64_encode(Version::PRODUCT),
				'release'         => base64_encode(Version::MAJOR_VERSION . '.' . Version::MINOR_VERSION),
				'dev_level'       => base64_encode($devLevel),
				'installfromon'   => $installfrom ? 1 : 0,
				'btntxt'          => Text::_('COM_INSTALLER_MSG_INSTALL_ENTER_A_URL', true),
				'pv'              => base64_encode($manifest->version),
				'updateavail1'    => Text::_('COM_INSTALLER_WEBINSTALLER_INSTALL_UPDATE_AVAILABLE', true),
				'updateavail2'    => Text::_('JLIB_INSTALLER_UPDATE', true),
			]
		);

		$javascript = <<<END
jQuery(document).ready(function ($) {
	var options = Joomla.getOptions('plg_installer_webinstaller', {});

	if (options.installfromon) {
		$('#myTabTabs a[href="#web"]').click();
	}

	var link = $('#myTabTabs a[href="#web"]').get(0);

	$(link).closest('li').click(function () {
		if (!Joomla.apps.loaded) {
			Joomla.apps.initialize();
		}
	});
	
	if (options.installfrom_url != '') {
		$(link).closest('li').click();
	}

	$('#myTabTabs a[href="#web"]').on('shown.bs.tab', function () {
		if (!Joomla.apps.loaded) {
			Joomla.apps.initialize();
		}
	});
});
		
END;
		$doc->addScriptDeclaration($javascript);
	}

	private function isRTL()
	{
		if ($this->rtl === null)
		{
			$this->rtl = strtolower(Factory::getDocument()->getDirection()) == 'rtl' ? 1 : 0;
		}

		return $this->rtl;
	}

	private function getInstallFrom()
	{
		if (is_null($this->installfrom))
		{
			$installfrom = base64_decode($this->app->input->getBase64('installfrom', ''));

			$field = new SimpleXMLElement('<field></field>');

			if ((new UrlRule)->test($field, $installfrom) && preg_match('/\.xml\s*$/', $installfrom))
			{
				$update = new Update;
				$update->loadFromXML($installfrom);
				$package_url = trim($update->get('downloadurl', false)->_data);

				if ($package_url)
				{
					$installfrom = $package_url;
				}
			}

			$this->installfrom = $installfrom;
		}

		return $this->installfrom;
	}

	private function getChanges()
	{
		$installfrom   = $this->getInstallFrom();
		$installfromon = $installfrom ? 1 : 0;
		$dir           = '';

		if ($this->isRTL())
		{
			$dir = ' dir="ltr"';
		}

		echo HTMLHelper::_('bootstrap.addTab', 'myTab', 'web', Text::_('COM_INSTALLER_INSTALL_FROM_WEB', true)); ?>
			<div id="jed-container" class="tab-pane">
				<div class="well" id="web-loader">
					<h2><?php echo Text::_('COM_INSTALLER_WEBINSTALLER_INSTALL_WEB_LOADING'); ?></h2>
				</div>
				<div class="alert alert-error" id="web-loader-error" style="display:none">
					<a class="close" data-dismiss="alert">×</a><?php echo Text::_('COM_INSTALLER_WEBINSTALLER_INSTALL_WEB_LOADING_ERROR'); ?>
				</div>
			</div>

			<fieldset class="uploadform" id="uploadform-web" style="display:none"<?php echo $dir; ?>>
				<div class="control-group">
					<strong><?php echo Text::_('COM_INSTALLER_WEBINSTALLER_INSTALL_WEB_CONFIRM'); ?></strong><br />
					<span id="uploadform-web-name-label"><?php echo Text::_('COM_INSTALLER_WEBINSTALLER_INSTALL_WEB_CONFIRM_NAME'); ?>:</span> <span id="uploadform-web-name"></span><br />
					<?php echo Text::_('COM_INSTALLER_WEBINSTALLER_INSTALL_WEB_CONFIRM_URL'); ?>: <span id="uploadform-web-url"></span>
				</div>
				<div class="form-actions">
					<input type="button" class="btn btn-primary" value="<?php echo Text::_('COM_INSTALLER_INSTALL_BUTTON'); ?>" onclick="Joomla.submitbutton<?php echo $installfrom != '' ? 4 : 5; ?>()" />
					<input type="button" class="btn btn-secondary" value="<?php echo Text::_('JCANCEL'); ?>" onclick="Joomla.installfromwebcancel()" />
				</div>
			</fieldset>

		<?php echo HTMLHelper::_('bootstrap.endTab');

	}
}
