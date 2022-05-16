<?php
/**
 * @package    Joomla.Administrator
 * @subpackage com_users
 *
 * @copyright  (C) 2022 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Users\Administrator\Model;

use Exception;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Event\GenericEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\User\User;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Component\Users\Administrator\DataShape\CaptiveRenderOptions;
use Joomla\Component\Users\Administrator\Helper\Tfa as TfaHelper;
use Joomla\Component\Users\Administrator\Table\TfaTable;
use Joomla\Event\Event;

/**
 * Captive Two Factor Authentication page's model
 *
 * @since __DEPLOY_VERSION__
 */
class CaptiveModel extends BaseDatabaseModel
{
	/**
	 * Cache of the names of the currently active TFA Methods
	 *
	 * @var  array|null
	 * @since __DEPLOY_VERSION__
	 */
	protected $activeTFAMethodNames = null;

	/**
	 * Prevents Joomla from displaying any modules.
	 *
	 * This is implemented with a trick. If you use jdoc tags to load modules the JDocumentRendererHtmlModules
	 * uses JModuleHelper::getModules() to load the list of modules to render. This goes through JModuleHelper::load()
	 * which triggers the onAfterModuleList event after cleaning up the module list from duplicates. By resetting
	 * the list to an empty array we force Joomla to not display any modules.
	 *
	 * Similar code paths are followed by any canonical code which tries to load modules. So even if your template does
	 * not use jdoc tags this code will still work as expected.
	 *
	 * @param   CMSApplication|null  $app  The CMS application to manipulate
	 *
	 * @return  void
	 * @throws  Exception
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function suppressAllModules(CMSApplication $app = null): void
	{
		if (is_null($app))
		{
			$app = Factory::getApplication();
		}

		$app->registerEvent('onAfterModuleList', [$this, 'onAfterModuleList']);
	}

	/**
	 * Get the TFA records for the user which correspond to active plugins
	 *
	 * @param   User|null  $user                The user for which to fetch records. Skip to use the current user.
	 * @param   bool       $includeBackupCodes  Should I include the backup codes record?
	 *
	 * @return  array
	 * @throws  Exception
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function getRecords(User $user = null, bool $includeBackupCodes = false): array
	{
		if (is_null($user))
		{
			$user = Factory::getApplication()->getIdentity()
				?: Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById(0);
		}

		// Get the user's TFA records
		$records = TfaHelper::getUserTfaRecords($user->id);

		// No TFA Methods? Then we obviously don't need to display a Captive login page.
		if (empty($records))
		{
			return [];
		}

		// Get the enabled TFA Methods' names
		$methodNames = $this->getActiveMethodNames();

		// Filter the records based on currently active TFA Methods
		$ret = [];

		$methodNames[] = 'backupcodes';
		$methodNames   = array_unique($methodNames);

		if (!$includeBackupCodes)
		{
			$methodNames = array_filter(
				$methodNames,
				function ($method)
				{
					return $method != 'backupcodes';
				}
			);
		}

		foreach ($records as $record)
		{
			// Backup codes must not be included in the list. We add them in the View, at the end of the list.
			if (in_array($record->method, $methodNames))
			{
				$ret[$record->id] = $record;
			}
		}

		return $ret;
	}

	/**
	 * Return all the active TFA Methods' names
	 *
	 * @return  array
	 * @since __DEPLOY_VERSION__
	 */
	private function getActiveMethodNames(): ?array
	{
		if (!is_null($this->activeTFAMethodNames))
		{
			return $this->activeTFAMethodNames;
		}

		// Let's get a list of all currently active TFA Methods
		$tfaMethods = TfaHelper::getTfaMethods();

		// If no TFA Method is active we can't really display a Captive login page.
		if (empty($tfaMethods))
		{
			$this->activeTFAMethodNames = [];

			return $this->activeTFAMethodNames;
		}

		// Get a list of just the Method names
		$this->activeTFAMethodNames = [];

		foreach ($tfaMethods as $tfaMethod)
		{
			$this->activeTFAMethodNames[] = $tfaMethod['name'];
		}

		return $this->activeTFAMethodNames;
	}

	/**
	 * Get the currently selected TFA record for the current user. If the record ID is empty, it does not correspond to
	 * the currently logged in user or does not correspond to an active plugin null is returned instead.
	 *
	 * @param   User|null  $user  The user for which to fetch records. Skip to use the current user.
	 *
	 * @return  TfaTable|null
	 * @throws  Exception
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function getRecord(?User $user = null): ?TfaTable
	{
		$id = (int) $this->getState('record_id', null);

		if ($id <= 0)
		{
			return null;
		}

		if (is_null($user))
		{
			$user = Factory::getApplication()->getIdentity()
				?: Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById(0);
		}

		/** @var TfaTable $record */
		$record = $this->getTable('Tfa', 'Administrator');
		$loaded = $record->load(
			[
				'user_id' => $user->id,
				'id'      => $id,
			]
		);

		if (!$loaded)
		{
			return null;
		}

		$methodNames = $this->getActiveMethodNames();

		if (!in_array($record->method, $methodNames) && ($record->method != 'backupcodes'))
		{
			return null;
		}

		return $record;
	}

	/**
	 * Load the Captive login page render options for a specific TFA record
	 *
	 * @param   TfaTable  $record  The TFA record to process
	 *
	 * @return  CaptiveRenderOptions  The rendering options
	 * @since __DEPLOY_VERSION__
	 */
	public function loadCaptiveRenderOptions(?TfaTable $record): CaptiveRenderOptions
	{
		$renderOptions = new CaptiveRenderOptions;

		if (empty($record))
		{
			return $renderOptions;
		}

		$results = Factory::getApplication()->triggerEvent(
			'onUserTwofactorCaptive',
			new GenericEvent('onUserTwofactorCaptive', ['record' => $record])
		);

		if (empty($results))
		{
			return $renderOptions;
		}

		foreach ($results as $result)
		{
			if (empty($result))
			{
				continue;
			}

			return $renderOptions->merge($result);
		}

		return $renderOptions;
	}

	/**
	 * Returns the title to display in the Captive login page, or an empty string if no title is to be displayed.
	 *
	 * @return  string
	 * @since __DEPLOY_VERSION__
	 */
	public function getPageTitle(): string
	{
		// In the frontend we can choose if we will display a title
		$showTitle = (bool) ComponentHelper::getParams('com_users')
			->get('frontend_show_title', 1);

		if (!$showTitle)
		{
			return '';
		}

		return Text::_('COM_USERS_USER_TWO_FACTOR_AUTH');
	}

	/**
	 * Translate a TFA Method's name into its human-readable, display name
	 *
	 * @param   string  $name  The internal TFA Method name
	 *
	 * @return  string
	 * @since __DEPLOY_VERSION__
	 */
	public function translateMethodName(string $name): string
	{
		static $map = null;

		if (!is_array($map))
		{
			$map        = [];
			$tfaMethods = TfaHelper::getTfaMethods();

			if (!empty($tfaMethods))
			{
				foreach ($tfaMethods as $tfaMethod)
				{
					$map[$tfaMethod['name']] = $tfaMethod['display'];
				}
			}
		}

		if ($name == 'backupcodes')
		{
			return Text::_('COM_USERS_USER_OTEPS');
		}

		return $map[$name] ?? $name;
	}

	/**
	 * Translate a TFA Method's name into the relative URL if its logo image
	 *
	 * @param   string  $name  The internal TFA Method name
	 *
	 * @return  string
	 * @since __DEPLOY_VERSION__
	 */
	public function getMethodImage(string $name): string
	{
		static $map = null;

		if (!is_array($map))
		{
			$map        = [];
			$tfaMethods = TfaHelper::getTfaMethods();

			if (!empty($tfaMethods))
			{
				foreach ($tfaMethods as $tfaMethod)
				{
					$map[$tfaMethod['name']] = $tfaMethod['image'];
				}
			}
		}

		if ($name == 'backupcodes')
		{
			return 'media/com_users/images/emergency.svg';
		}

		return $map[$name] ?? $name;
	}

	/**
	 * Process the modules list on Joomla! 4.
	 *
	 * Joomla! 4.x is passing an Event object. The first argument of the event object is the array of modules. After
	 * filtering it we have to overwrite the event argument (NOT just return the new list of modules). If a future
	 * version of Joomla! uses immutable events we'll have to use Reflection to do that or Joomla! would have to fix
	 * the way this event is handled, taking its return into account. For now, we just abuse the mutable event
	 * properties - a feature of the event objects we discussed in the Joomla! 4 Working Group back in August 2015.
	 *
	 * @param   Event  $event  The Joomla! event object
	 *
	 * @return  void
	 * @throws  Exception
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function onAfterModuleList(Event $event): void
	{
		$modules = $event->getArgument(0);

		if (empty($modules))
		{
			return;
		}

		$this->filterModules($modules);

		$event->setArgument(0, $modules);
	}

	/**
	 * This is the Method which actually filters the sites modules based on the allowed module positions specified by
	 * the user.
	 *
	 * @param   array  $modules  The list of the site's modules. Passed by reference.
	 *
	 * @return  void  The by-reference value is modified instead.
	 * @since __DEPLOY_VERSION__
	 * @throws  Exception
	 */
	private function filterModules(array &$modules): void
	{
		$allowedPositions = $this->getAllowedModulePositions();

		if (empty($allowedPositions))
		{
			$modules = [];

			return;
		}

		$filtered = [];

		foreach ($modules as $module)
		{
			if (in_array($module->position, $allowedPositions))
			{
				$filtered[] = $module;
			}
		}

		$modules = $filtered;
	}

	/**
	 * Get a list of module positions we are allowed to display
	 *
	 * @return  array
	 * @throws  Exception
	 *
	 * @since __DEPLOY_VERSION__
	 */
	private function getAllowedModulePositions(): array
	{
		$isAdmin = Factory::getApplication()->isClient('administrator');

		// Load the list of allowed module positions from the component's settings. May be different for front- and back-end
		$configKey = 'allowed_positions_' . ($isAdmin ? 'backend' : 'frontend');
		$res       = ComponentHelper::getParams('com_users')->get($configKey, []);

		// In the backend we must always add the 'title' module position
		if ($isAdmin)
		{
			$res[] = 'title';
		}

		return $res;
	}

}
