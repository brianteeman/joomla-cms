<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_users
 *
 * @copyright   (C) 2022 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Users\Administrator\Controller;

use Exception;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Event\GenericEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController as BaseControllerAlias;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\Router\Route;
use Joomla\CMS\User\User;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Component\Users\Administrator\Helper\Tfa as TfaHelper;
use Joomla\Component\Users\Administrator\Model\BackupcodesModel;
use Joomla\Component\Users\Administrator\Model\MethodModel;
use Joomla\Component\Users\Administrator\Table\TfaTable;
use Joomla\Input\Input;
use RuntimeException;

/**
 * Two Factor Authentication method controller
 *
 * @since __DEPLOY_VERSION__
 */
class MethodController extends BaseControllerAlias
{
	/**
	 * Public constructor
	 *
	 * @param   array                     $config   Plugin configuration
	 * @param   MVCFactoryInterface|null  $factory  MVC Factory for the com_users component
	 * @param   CMSApplication|null       $app      CMS application object
	 * @param   Input|null                $input    Joomla CMS input object
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function __construct(array $config = [], MVCFactoryInterface $factory = null, ?CMSApplication $app = null, ?Input $input = null)
	{
		// We have to tell Joomla what is the name of the view, otherwise it defaults to the name of the *component*.
		$config['default_view'] = 'method';
		$config['default_task'] = 'add';

		parent::__construct($config, $factory, $app, $input);
	}

	/**
	 * Execute a task by triggering a Method in the derived class.
	 *
	 * @param   string  $task    The task to perform. If no matching task is found, the '__default' task is executed, if
	 *                           defined.
	 *
	 * @return  mixed   The value returned by the called Method.
	 *
	 * @throws  Exception
	 * @since   __DEPLOY_VERSION__
	 */
	public function execute($task)
	{
		if (empty($task) || $task === 'display')
		{
			$task = 'add';
		}

		return parent::execute($task);
	}

	/**
	 * Add a new TFA Method
	 *
	 * @param   boolean        $cachable   Ignored. This page is never cached.
	 * @param   boolean|array  $urlparams  Ignored. This page is never cached.
	 *
	 * @return  void
	 * @throws  Exception
	 * @since   __DEPLOY_VERSION__
	 */
	public function add($cachable = false, $urlparams = []): void
	{
		$this->assertLoggedInUser();

		// Make sure I am allowed to edit the specified user
		$userId = $this->input->getInt('user_id', null);
		$user   = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($userId);

		$this->assertCanEdit($user);

		// Also make sure the Method really does exist
		$method = $this->input->getCmd('method');
		$this->assertMethodExists($method);

		/** @var MethodModel $model */
		$model = $this->getModel('Method');
		$model->setState('method', $method);

		// Pass the return URL to the view
		$returnURL  = $this->input->getBase64('returnurl');
		$viewLayout = $this->input->get('layout', 'default', 'string');
		$view       = $this->getView('Method', 'html');
		$view->setLayout($viewLayout);
		$view->returnURL = $returnURL;
		$view->user      = $user;
		$view->document  = $this->app->getDocument();

		$view->setModel($model, true);

		$this->app->triggerEvent(
			'onComUsersControllerMethodBeforeAdd',
			new GenericEvent('onComUsersControllerMethodBeforeAdd', [$user, $method])
		);

		$view->display();
	}

	/**
	 * Edit an existing TFA Method
	 *
	 * @param   boolean        $cachable   Ignored. This page is never cached.
	 * @param   boolean|array  $urlparams  Ignored. This page is never cached.
	 *
	 * @return  void
	 * @throws  Exception
	 * @since   __DEPLOY_VERSION__
	 */
	public function edit($cachable = false, $urlparams = []): void
	{
		$this->assertLoggedInUser();

		// Make sure I am allowed to edit the specified user
		$userId = $this->input->getInt('user_id', null);
		$user   = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($userId);

		$this->assertCanEdit($user);

		// Also make sure the Method really does exist
		$id     = $this->input->getInt('id');
		$record = $this->assertValidRecordId($id, $user);

		if ($id <= 0)
		{
			throw new RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		/** @var MethodModel $model */
		$model = $this->getModel('Method');
		$model->setState('id', $id);

		// Pass the return URL to the view
		$returnURL  = $this->input->getBase64('returnurl');
		$viewLayout = $this->input->get('layout', 'default', 'string');
		$view       = $this->getView('Method', 'html');
		$view->setLayout($viewLayout);
		$view->returnURL = $returnURL;
		$view->user      = $user;
		$view->document  = $this->app->getDocument();

		$view->setModel($model, true);

		$this->app->triggerEvent(
			'onComUsersControllerMethodBeforeEdit',
			new GenericEvent('onComUsersControllerMethodBeforeEdit', [$id, $user])
		);

		$view->display();
	}

	/**
	 * Regenerate backup codes
	 *
	 * @param   boolean        $cachable   Ignored. This page is never cached.
	 * @param   boolean|array  $urlparams  Ignored. This page is never cached.
	 *
	 * @return  void
	 * @throws Exception
	 * @since   __DEPLOY_VERSION__
	 */
	public function regenerateBackupCodes($cachable = false, $urlparams = []): void
	{
		$this->assertLoggedInUser();

		$this->checkToken($this->input->getMethod());

		// Make sure I am allowed to edit the specified user
		$userId = $this->input->getInt('user_id', null);
		$user    = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($userId);
		$this->assertCanEdit($user);

		/** @var BackupcodesModel $model */
		$model = $this->getModel('Backupcodes');
		$model->regenerateBackupCodes($user);

		$backupCodesRecord = $model->getBackupCodesRecord($user);

		// Redirect
		$redirectUrl = 'index.php?option=com_users&task=method.edit&user_id=' . $userId . '&id=' . $backupCodesRecord->id;
		$returnURL   = $this->input->getBase64('returnurl');

		if (!empty($returnURL))
		{
			$redirectUrl .= '&returnurl=' . $returnURL;
		}

		$this->setRedirect(Route::_($redirectUrl, false));

		$this->app->triggerEvent(
			'onComUsersControllerMethodAfterRegenerateBackupCodes',
			new GenericEvent('onComUsersControllerMethodAfterRegenerateBackupCodes')
		);
	}

	/**
	 * Delete an existing TFA Method
	 *
	 * @param   boolean        $cachable   Ignored. This page is never cached.
	 * @param   boolean|array  $urlparams  Ignored. This page is never cached.
	 *
	 * @return  void
	 * @since   __DEPLOY_VERSION__
	 */
	public function delete($cachable = false, $urlparams = []): void
	{
		$this->assertLoggedInUser();

		$this->checkToken($this->input->getMethod());

		// Make sure I am allowed to edit the specified user
		$userId = $this->input->getInt('user_id', null);
		$user    = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($userId);
		$this->assertCanEdit($user);

		// Also make sure the Method really does exist
		$id     = $this->input->getInt('id');
		$record = $this->assertValidRecordId($id, $user);

		if ($id <= 0)
		{
			throw new RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		$type    = null;
		$message = null;

		$this->app->triggerEvent(
			'onComUsersControllerMethodBeforeDelete',
			new GenericEvent('onComUsersControllerMethodBeforeDelete', [$id, $user])
		);

		try
		{
			$record->delete();
		}
		catch (Exception $e)
		{
			$message = $e->getMessage();
			$type    = 'error';
		}

		// Redirect
		$url       = Route::_('index.php?option=com_users&task=methods.display&user_id=' . $userId, false);
		$returnURL = $this->input->getBase64('returnurl');

		if (!empty($returnURL))
		{
			$url = base64_decode($returnURL);
		}

		$this->setRedirect($url, $message, $type);
	}

	/**
	 * Save the TFA Method
	 *
	 * @param   boolean        $cachable   Ignored. This page is never cached.
	 * @param   boolean|array  $urlparams  Ignored. This page is never cached.
	 *
	 * @return  void
	 * @since   __DEPLOY_VERSION__
	 */
	public function save($cachable = false, $urlparams = []): void
	{
		$this->assertLoggedInUser();

		$this->checkToken($this->input->getMethod());

		// Make sure I am allowed to edit the specified user
		$userId = $this->input->getInt('user_id', null);
		$user    = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($userId);
		$this->assertCanEdit($user);

		// Redirect
		$url       = Route::_('index.php?option=com_users&task=methods.display&user_id=' . $userId, false);
		$returnURL = $this->input->getBase64('returnurl');

		if (!empty($returnURL))
		{
			$url = base64_decode($returnURL);
		}

		// The record must either be new (ID zero) or exist
		$id     = $this->input->getInt('id', 0);
		$record = $this->assertValidRecordId($id, $user);

		// If it's a new record we need to read the Method from the request and update the (not yet created) record.
		if ($record->id == 0)
		{
			$methodName = $this->input->getCmd('method');
			$this->assertMethodExists($methodName);
			$record->method = $methodName;
		}

		/** @var MethodModel $model */
		$model = $this->getModel('Method');

		// Ask the plugin to validate the input by calling onUserTwofactorSaveSetup
		$result = [];
		$input  = $this->app->input;

		$this->app->triggerEvent(
			'onComUsersControllerMethodBeforeSave',
			new GenericEvent('onComUsersControllerMethodBeforeSave', [$id, $user])
		);

		try
		{
			$pluginResults = $this->app->triggerEvent(
				'onUserTwofactorSaveSetup',
				new GenericEvent('onUserTwofactorSaveSetup',
					[
						'record' => $record,
						'input'  => $input
					]
				)
			);

			foreach ($pluginResults as $pluginResult)
			{
				$result = array_merge($result, $pluginResult);
			}
		}
		catch (RuntimeException $e)
		{
			// Go back to the edit page
			$nonSefUrl = 'index.php?option=com_users&task=method.';

			if ($id)
			{
				$nonSefUrl .= 'edit&id=' . (int) $id;
			}
			else
			{
				$nonSefUrl .= 'add&method=' . $record->method;
			}

			$nonSefUrl .= '&user_id=' . $userId;

			if (!empty($returnURL))
			{
				$nonSefUrl .= '&returnurl=' . urlencode($returnURL);
			}

			$url = Route::_($nonSefUrl, false);
			$this->setRedirect($url, $e->getMessage(), 'error');

			return;
		}

		// Update the record's options with the plugin response
		$title = $this->input->getString('title', null);
		$title = trim($title);

		if (empty($title))
		{
			$method = $model->getMethod($record->method);
			$title  = $method['display'];
		}

		// Update the record's "default" flag
		$default         = $this->input->getBool('default', false);
		$record->title   = $title;
		$record->options = $result;
		$record->default = $default ? 1 : 0;

		// Ask the model to save the record
		$saved = $record->store();

		if (!$saved)
		{
			// Go back to the edit page
			$nonSefUrl = 'index.php?option=com_users&task=method.';

			if ($id)
			{
				$nonSefUrl .= 'edit&id=' . (int) $id;
			}
			else
			{
				$nonSefUrl .= 'add';
			}

			$nonSefUrl .= '&user_id=' . $userId;

			if (!empty($returnURL))
			{
				$nonSefUrl .= '&returnurl=' . urlencode($returnURL);
			}

			$url = Route::_($nonSefUrl, false);
			$this->setRedirect($url, $record->getError(), 'error');

			return;
		}

		$this->setRedirect($url);
	}

	/**
	 * Assert that the provided ID is a valid record identified for the given user
	 *
	 * @param   int        $id    Record ID to check
	 * @param   User|null  $user  User record. Null to use current user.
	 *
	 * @return  TfaTable  The loaded record
	 * @since   __DEPLOY_VERSION__
	 */
	private function assertValidRecordId($id, ?User $user = null): TfaTable
	{
		if (is_null($user))
		{
			$user = $this->app->getIdentity()
				?: Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById(0);
		}

		/** @var MethodModel $model */
		$model = $this->getModel('Method');

		$model->setState('id', $id);

		$record = $model->getRecord($user);

		// phpcs:ignore
		if (is_null($record) || ($record->id != $id) || ($record->user_id != $user->id))
		{
			throw new RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		return $record;
	}

	/**
	 * Assert that the user is logged in.
	 *
	 * @param   User|null  $user  User record. Null to use current user.
	 *
	 * @return  void
	 * @throws  RuntimeException|Exception  When the user is a guest (not logged in)
	 * @since   __DEPLOY_VERSION__
	 */
	private function assertCanEdit(?User $user = null): void
	{
		if (is_null($user))
		{
			$user = $this->app->getIdentity()
				?: Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById(0);
		}

		if (!TfaHelper::canEditUser($user))
		{
			throw new RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}
	}

	/**
	 * Assert that the specified TFA Method exists, is activated and enabled for the current user
	 *
	 * @param   string|null  $method  The Method to check
	 *
	 * @return  void
	 * @since   __DEPLOY_VERSION__
	 */
	private function assertMethodExists(?string $method): void
	{
		/** @var MethodModel $model */
		$model = $this->getModel('Method');

		if (empty($method) || !$model->methodExists($method))
		{
			throw new RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}
	}

	/**
	 * Assert that there is a logged in user.
	 *
	 * @return  void
	 * @since   __DEPLOY_VERSION__
	 */
	private function assertLoggedInUser(): void
	{
		$user = $this->app->getIdentity()
			?: Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById(0);

		if ($user->guest)
		{
			throw new RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}
	}
}
