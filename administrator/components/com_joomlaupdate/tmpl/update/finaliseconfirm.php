<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_joomlaupdate
 *
 * @copyright   Copyright (C) 2005 - 2019 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\AuthenticationHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

HTMLHelper::_('behavior.keepalive');

$twofactormethods = AuthenticationHelper::getTwoFactorMethods();

?>

<div class="alert warning">
	<h4 class="alert-heading">
		<?php echo Text::_('COM_JOOMLAUPDATE_VIEW_UPDATE_FINALISE_HEAD'); ?>
	</h4>
	<p>
		<?php echo Text::sprintf('COM_JOOMLAUPDATE_VIEW_UPDATE_FINALISE_HEAD_DESC', Factory::getApplication()->get('sitename')); ?>
	</p>
</div>

<hr>

<form action="<?php echo Route::_('index.php', true); ?>" method="post" id="form-login" class="text-center">
	<fieldset class="loginform">
		<legend><?php echo Text::_('COM_JOOMLAUPDATE_CONFIRM'); ?></legend>
		<div class="control-group">
			<div class="controls">
				<div class="input-group">
					<input name="username" tabindex="1" id="mod-login-username" type="text" class="form-control" placeholder="<?php echo Text::_('JGLOBAL_USERNAME'); ?>" size="15" autofocus="true">
					<span class="input-group-append">
						<span class="input-group-text">
							<span class="fas fa-user" aria-hidden="true"></span>
							<label for="mod-login-username" class="sr-only">
								<?php echo Text::_('JGLOBAL_USERNAME'); ?>
							</label>
						</span>
					</span>
				</div>
			</div>
		</div>
		<div class="control-group">
			<div class="controls">
				<div class="input-addon">
					<input name="passwd" tabindex="2" id="mod-login-password" type="password" class="form-control" placeholder="<?php echo Text::_('JGLOBAL_PASSWORD'); ?>" size="15">
					<span class="input-group-append">
						<span class="input-group-text">
							<span class="fas fa-lock" aria-hidden="true"></span>
							<label for="mod-login-password" class="sr-only">
								<?php echo Text::_('JGLOBAL_PASSWORD'); ?>
							</label>
						</span>
					</span>
				</div>
			</div>
		</div>
		<?php if (count($twofactormethods) > 1) : ?>
			<div class="control-group">
				<div class="controls">
					<div class="input-group">
						<input name="secretkey" autocomplete="off" tabindex="3" id="mod-login-secretkey" type="text" class="form-control" placeholder="<?php echo Text::_('JGLOBAL_SECRETKEY'); ?>" size="15">
						<span class="input-group-append">
							<span class="input-group-text" title="<?php echo Text::_('JGLOBAL_SECRETKEY_HELP'); ?>">
								<span class="fas fa-star" aria-hidden="true"></span>
								<label for="mod-login-secretkey" class="sr-only">
									<?php echo Text::_('JGLOBAL_SECRETKEY'); ?>
								</label>
							</span>
						</span>
					</div>
				</div>
			</div>
		<?php endif; ?>
		<div class="control-group">
			<div class="controls">
				<div class="btn-group">
					<a tabindex="4" class="btn btn-danger btn-sm" href="index.php?option=com_joomlaupdate">
						<span class="fas fa-times icon-white" aria-hidden="true"></span> <?php echo Text::_('JCANCEL'); ?>
					</a>
					<button tabindex="5" class="btn btn-primary btn-lg">
						<span class="fas fa-play icon-white" aria-hidden="true"></span> <?php echo Text::_('COM_JOOMLAUPDATE_VIEW_UPDATE_FINALISE_CONFIRM_AND_CONTINUE'); ?>
					</button>
				</div>
			</div>
		</div>

		<input type="hidden" name="option" value="com_joomlaupdate">
		<input type="hidden" name="task" value="update.finaliseconfirm">
		<?php echo HTMLHelper::_('form.token'); ?>
	</fieldset>
</form>
