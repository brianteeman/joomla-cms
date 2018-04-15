<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  User.privacyconsent
 *
 * @copyright   Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\Utilities\ArrayHelper;

/**
 * An example custom privacyconsent plugin.
 *
 * @since  __DEPLOY_VERSION__
 */
class PlgUserPrivacyconsent extends JPlugin
{
	/**
	 * Load the language file on instantiation.
	 *
	 * @var    boolean
	 * @since  __DEPLOY_VERSION__
	 */
	protected $autoloadLanguage = true;

	/**
	 * Application object.
	 *
	 * @var    JApplicationCms
	 * @since  1.0
	 */
	protected $app;
	
	/**
	 * Constructor
	 *
	 * @param   object  &$subject  The object to observe
	 * @param   array   $config    An array that holds the plugin configuration
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function __construct(&$subject, $config)
	{
		parent::__construct($subject, $config);
		JFormHelper::addFieldPath(__DIR__ . '/field');
	}

	/**
	 * Adds additional fields to the user editing form
	 *
	 * @param   JForm  $form  The form to be altered.
	 * @param   mixed  $data  The associated data for the form.
	 *
	 * @return  boolean
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function onContentPrepareForm($form, $data)
	{
		if (!($form instanceof JForm))
		{
			$this->_subject->setError('JERROR_NOT_A_FORM');

			return false;
		}

		// Check we are manipulating a valid form - we do not display this in the admin users form or profile view.
		$name 	= $form->getName();
		$layout = $this->app->input->get('layout', 'default');
		$view	= $this->app->input->get('view', 'default');

		if (!in_array($name, array('com_admin.profile', 'com_users.profile', 'com_users.registration')) || $layout != "edit" && $view != "registration")
		{
			return true;
		}

		// Add the registration fields to the form.
		JForm::addFormPath(__DIR__ . '/privacyconsent');
		$form->loadFile('privacyconsent');

		$fields = array(
			'privacy',
		);

		if (is_object($data))
		{

			$userId = isset($data->id) ? $data->id : 0;

			if ($userId > 0)
			{
				// Load the profile data from the database.
				$db = JFactory::getDbo();

				$query = $db->getQuery(true)
					->select($db->quoteName('profile_value'))
					->from($db->quoteName('#__user_profiles'))
					->where($db->quoteName('user_id') . ' = ' . (int) $userId)
					->where($db->quoteName('profile_key') . ' LIKE ' . $db->quote('consent'))
					->order($db->quoteName('ordering') . ' ASC');
				$db->setQuery($query);

				try
				{
					$results = $db->loadRowList();
				}
				catch (RuntimeException $e)
				{
					$this->_subject->setError($e->getMessage());

					return false;
				}

				if (!empty($results[0]) == 1)
				{
					$form->removeField('privacy', 'privacyconsent');

					return true;
				}
			}
		}

		$privacyarticle	= $this->params->get('privacy_article');
		$privacynote	= $this->params->get('privacy_note');

		// Push the privacy article ID into the privacy field.
		$form->setFieldAttribute('privacy', 'article', $privacyarticle, 'privacyconsent');
		$form->setFieldAttribute('privacy', 'note', $privacynote, 'privacyconsent');
	}

	/**
	 * Method is called before user data is stored in the database
	 *
	 * @param   array    $user   Holds the old user data.
	 * @param   boolean  $isNew  True if a new user is stored.
	 * @param   array    $data   Holds the new user data.
	 *
	 * @return  boolean
	 *
	 * @since   __DEPLOY_VERSION__
	 * @throws  InvalidArgumentException on missing required data.
	 */
	public function onUserBeforeSave($user, $isNew, $data)
	{
		// Check that the privacy is checked if required ie only in registration from frontend.
		$option = $this->app->input->getCmd('option');
		$form   = $this->app->input->post->get('jform', array(), 'array');

		if ($this->app->isClient('site') && (!$form['privacyconsent']['privacy']) || (!$form['privacyconsent']['privacy']) && $option === 'com_admin')
		{
			throw new InvalidArgumentException(JText::_('PLG_USER_PRIVACY_FIELD_ERROR'));
		}

		return true;
	}

	/**
	 * Saves user privacy confirmation and note
	 *
	 * @param   array    $data    entered user data
	 * @param   boolean  $isNew   true if this is a new user
	 * @param   boolean  $result  true if saving the user worked
	 * @param   string   $error   error message
	 *
	 * @return  boolean
	 */
	public function onUserAfterSave($data, $isNew, $result, $error)
	{
		$option	= $this->app->input->getCmd('option');

		// Only create an entry on front-end user creation/update and admin profile
		if ($this->app->isClient('administrator') && $option != 'com_admin')
		{
			return;
		}

		// Get the user's ID
		$userId = ArrayHelper::getValue($data, 'id', 0, 'int');

		// Get the user's IP address
		$ip = $this->app->input->server->get('REMOTE_ADDR');

		// Get the user agent string
		$user_agent = $this->app->input->server->get('HTTP_USER_AGENT');

		// Get the date in DB format
		$now = JFactory::getDate()->toSql();

		// Create the user note
		$userNote = (object) array(
			'user_id'         => $userId,
			'catid'           => 0,
			'subject'         => JText::_('PLG_USER_PRIVACY_SUBJECT'),
			'body'            => JText::sprintf('PLG_USER_PRIVACY_BODY', $ip, $user_agent),
			'state'           => 1,
			'created_time'    => $now
		);

		try
		{
			$result = JFactory::getDbo()->insertObject('#__user_notes', $userNote);
		}
		catch (Exception $e)
		{
			// Do nothing if the save fails
		}

		// Create the consent confirmation
		$confirm = (object) array(
			'user_id'	=> $userId,
			'profile_key'	=> 'consent',
			'profile_value'	=> 1
		);

		try
		{
			$result = JFactory::getDbo()->insertObject('#__user_profiles', $confirm);
		}
		catch (Exception $e)

		{
			// Do nothing if the save fails
		}
		return true;
	}

	/**
	 * Remove all user privacy consent information for the given user ID
	 *
	 * Method is called after user data is deleted from the database
	 *
	 * @param   array    $user     Holds the user data
	 * @param   boolean  $success  True if user was succesfully stored in the database
	 * @param   string   $msg      Message
	 *
	 * @return  boolean
	 */
	public function onUserAfterDelete($user, $success, $msg)
	{
		if (!$success)
		{
			return false;
		}

		$userId = ArrayHelper::getValue($user, 'id', 0, 'int');

		if ($userId)
		{
			// Remove any user notes
			try
			{
				$db = JFactory::getDbo();

				$query = $db->getQuery(true)
					->delete($db->quoteName('#__user_notes'))
					->where($db->quoteName('user_id') . ' = ' . (int) $userId);
				$db->setQuery($query);
				$db->execute();
			}
			catch (Exception $e)
			{
				$this->_subject->setError($e->getMessage());

				return false;
			}

			// Remove any user profile fields
			try
			{
				$db = JFactory::getDbo();

				$query = $db->getQuery(true)
					->delete($db->quoteName('#__user_profiles'))
					->where($db->quoteName('user_id') . ' = ' . (int) $userId);
				$db->setQuery($query);
				$db->execute();
			}
			catch (Exception $e)
			{
				$this->_subject->setError($e->getMessage());

				return false;
			}
		}

		return true;
	}

	/**
	 * Check if a user has already consented when they login.
	 * If not will load the edit profile
	 *
	 * @param   array  $options  Array holding options
	 *
	 * @return  boolean  True on success
	 *
	 * @since   1.0
	 */
	public function onUserAfterLogin($options)
	{
		// Run this in frontend only
		if ($this->app->isClient('administrator'))
		{
			return;
		}

		$userId = JFactory::getUser()->id;
		$db = JFactory::getDbo();

		$query = $db->getQuery(true)
			->select('1')
			->from($db->quoteName('#__user_profiles'))
			->where($db->quoteName('user_id') . ' = ' . (int) $userId)
			->where($db->quoteName('profile_key') . ' = ' . $db->quote('consent'));
		$db->setQuery($query);

		$consent = $db->loadObjectList();

		if (count($consent) != 0)
		{
			return;
		}

		// If the count of $consent is 0 then redirect to com_users profile edit
		$this->app->enqueueMessage($this->getRedirectMessage(), 'notice');
		$this->app->redirect(\JRoute::_('index.php?option=com_users&view=profile&layout=edit', false));
	}

	/**
	 * Returns the configured redirect message and falls back to the default version.
	 *
	 * @return  string  redirect message
	 *
	 * @since   1.0
	 */
	private function getRedirectMessage()
	{
		$messageOnRedirect = trim($this->params->get('messageOnRedirect', ''));

		if (empty($messageOnRedirect))
		{
			return \JText::_('PLG_USER_PRIVACY_REDIRECT_MESSAGE_DEFAULT');
		}

		return $messageOnRedirect;
	}
}
