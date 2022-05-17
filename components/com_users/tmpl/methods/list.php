<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_users
 *
 * @copyright   (C) 2022 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// Prevent direct access
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\Users\Site\Model\MethodsModel;
use Joomla\Component\Users\Site\View\Methods\HtmlView;

// phpcs:ignoreFile
/** @var HtmlView $this */

HTMLHelper::_('bootstrap.tooltip', '.hasTooltip');

/** @var MethodsModel $model */
$model = $this->getModel();

?>
<div id="com-users-methods-list-container" class="p-1">
	<?php foreach($this->methods as $methodName => $method):
		$methodClass = 'com-users-methods-list-method-name-' . htmlentities($method['name'])
			. ($this->defaultMethod == $methodName ? ' com-users-methods-list-method-default' : '');
		?>
		<div class="com-users-methods-list-method <?php echo $methodClass?> my-3 p-2 border border-1">
			<div class="com-users-methods-list-method-header d-flex flex-wrap align-items-center gap-2">
				<div class="com-users-methods-list-method-image pt-1 px-3 pb-2">
					<img src="<?php echo Uri::root() . $method['image'] ?>"
						 alt="<?php echo $this->escape($method['name']) ?>"
						 class="img-fluid"
					>
				</div>
				<div class="com-users-methods-list-method-title flex-grow-1">
					<h4 class="fs-4 p-0 m-0 d-flex gap-3 align-items-center">
						<span class="me-1 flex-grow-1">
							<?php echo $method['display'] ?>
						</span>
						<?php if ($this->defaultMethod == $methodName): ?>
							<span id="com-users-methods-list-method-default-tag" class="badge bg-info me-1 fs-6">
								<?php echo Text::_('COM_USERS_TFA_LIST_DEFAULTTAG') ?>
							</span>
						<?php endif; ?>
					</h4>
				</div>
				<div class="com-users-methods-list-method-info">
					<span class="hasTooltip icon icon-info-circle icon-info-sign"
						  title="<?php echo $this->escape($method['shortinfo']) ?>"></span>
				</div>
			</div>

			<div class="com-users-methods-list-method-records-container">
				<?php if (count($method['active'])): ?>
					<div class="com-users-methods-list-method-records border-top border-dark pt-2 my-2">
						<?php  foreach($method['active'] as $record): ?>
							<div class="com-users-methods-list-method-record d-flex flex-row flex-wrap justify-content-start">
								<div class="com-users-methods-list-method-record-info flex-grow-1 d-flex flex-column align-items-start gap-1">
									<?php if ($methodName == 'backupcodes'): ?>
										<div class="alert alert-info">
											<h3 class="alert-heading fs-6">
												<span class="icon icon-info-circle icon-info-sign" aria-hidden="true"></span>
												<?php echo Text::sprintf('COM_USERS_TFA_OTEP_PRINT_PROMPT_HEAD', Route::_('index.php?option=com_users&task=method.edit&id=' . (int) $record->id . ($this->returnURL ? '&returnurl=' . $this->escape(urlencode($this->returnURL)) : '') . '&user_id=' . $this->user->id)) ?>
											</h3>
											<p class="text-muted">
												<?php echo Text::_('COM_USERS_TFA_OTEP_PRINT_PROMPT') ?>
											</p>
										</div>
									<?php else: ?>
										<div class="com-users-methods-list-method-record-title-container mb-1">
											<?php if ($record->default): ?>
												<span id="com-users-methods-list-method-default-badge-small"
													  class="text-warning me-1 hasTooltip"
													  title="<?php echo $this->escape(Text::_('COM_USERS_TFA_LIST_DEFAULTTAG')) ?>">
													<span class="icon icon-star" aria-hidden="true"></span>
													<span class="visually-hidden"><?php echo $this->escape(Text::_('COM_USERS_TFA_LIST_DEFAULTTAG')) ?></span>
												</span>
											<?php endif; ?>
											<span class="com-users-methods-list-method-record-title fs-4 fw-bold">
												<?php echo $this->escape($record->title); ?>
											</span>
										</div>
									<?php endif; ?>

									<div class="com-users-methods-list-method-record-lastused my-1 d-flex flex-row flex-wrap justify-content-start text-muted w-100">
										<span class="com-users-methods-list-method-record-createdon w-50">
											<?php echo Text::sprintf('COM_USERS_TFA_LBL_CREATEDON', $model->formatRelative($record->created_on)) ?>
										</span>
										<span class="com-users-methods-list-method-record-lastused-date w-50">
											<?php if ($record->last_used === null) : ?>
												<?php echo Text::sprintf('COM_USERS_TFA_LBL_LASTUSED', Text::_('JNEVER')); ?>
											<?php else : ?>
												<?php echo Text::sprintf('COM_USERS_TFA_LBL_LASTUSED', $model->formatRelative($record->last_used)) ?>
											<?php endif; ?>
										</span>
									</div>

								</div>

								<?php if ($methodName != 'backupcodes'): ?>
								<div class="com-users-methods-list-method-record-actions my-2 d-flex flex-row flex-wrap justify-content-center align-content-center align-items-start">
									<a class="com-users-methods-list-method-record-edit btn btn-secondary btn-sm mx-1"
									   href="<?php echo Route::_('index.php?option=com_users&task=method.edit&id=' . (int) $record->id . ($this->returnURL ? '&returnurl=' . $this->escape(urlencode($this->returnURL)) : '') . '&user_id=' . $this->user->id)?>">
										<span class="icon icon-pencil" aria-hidden="true"></span>
										<span class="visually-hidden"><?php echo Text::_('JACTION_EDIT') ?></span>
									</a>

									<?php if ($method['canDisable']): ?>
										<a class="com-users-methods-list-method-record-delete btn btn-danger btn-sm mx-1"
										   href="<?php echo Route::_('index.php?option=com_users&task=method.delete&id=' . (int) $record->id . ($this->returnURL ? '&returnurl=' . $this->escape(urlencode($this->returnURL)) : '') . '&user_id=' . $this->user->id . '&' . Factory::getApplication()->getFormToken() . '=1')?>"
										>
											<span class="icon icon-trash" aria-hidden="true"></span>
											<span class="visually-hidden"><?php echo Text::_('JACTION_DELETE') ?></span>
										</a>
									<?php endif; ?>
								</div>
								<?php endif; ?>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>

				<?php if (empty($method['active']) || $method['allowMultiple']): ?>
					<div class="com-users-methods-list-method-addnew-container">
						<a href="<?php echo Route::_('index.php?option=com_users&task=method.add&method=' . $this->escape(urlencode($method['name'])) . ($this->returnURL ? '&returnurl=' . $this->escape(urlencode($this->returnURL)) : '') . '&user_id=' . $this->user->id)?>"
						   class="com-users-methods-list-method-addnew btn btn-primary"
						>
							<?php echo Text::sprintf('COM_USERS_TFA_ADD_AUTHENTICATOR_OF_TYPE', $method['display']) ?>
						</a>
					</div>
				<?php endif; ?>
			</div>
		</div>
	<?php endforeach; ?>
</div>
