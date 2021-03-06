<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_modules
 *
 * @copyright   Copyright (C) 2005 - 2019 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Language\Text;
use Joomla\Component\Menus\Administrator\Helper\MenusHelper;
use Joomla\Component\Modules\Administrator\Helper\ModulesHelper;

// Initialise related data.
$menuTypes = MenusHelper::getMenuLinks();

HTMLHelper::_('script', 'legacy/treeselectmenu.min.js', array('version' => 'auto', 'relative' => true));
HTMLHelper::_('script', 'com_modules/admin-module-edit_assignment.min.js', array('version' => 'auto', 'relative' => true));
?>
<div class="control-group">
	<label id="jform_menus-lbl" class="control-label" for="jform_assignment"><?php echo Text::_('COM_MODULES_MODULE_ASSIGN'); ?></label>
	<div id="jform_menus" class="controls">
		<select class="custom-select" name="jform[assignment]" id="jform_assignment">
			<?php echo HTMLHelper::_('select.options', ModulesHelper::getAssignmentOptions($this->item->client_id), 'value', 'text', $this->item->assignment, true); ?>
		</select>
	</div>
</div>
<div id="menuselect-group" class="control-group">
	<label id="jform_menuselect-lbl" class="control-label" for="jform_menuselect"><?php echo Text::_('JGLOBAL_MENU_SELECTION'); ?></label>

	<div id="jform_menuselect" class="controls">
		<?php if (!empty($menuTypes)) : ?>
		<?php $id = 'jform_menuselect'; ?>

		<div class="card card-secondary">
			<div class="card-header">
				<span class="small"><?php echo Text::_('JSELECT'); ?>:
					<a id="treeCheckAll" href="javascript://"><?php echo Text::_('JALL'); ?></a>,
					<a id="treeUncheckAll" href="javascript://"><?php echo Text::_('JNONE'); ?></a>
				</span>
				<span class="width-20">|</span>
				<span class="small"><?php echo Text::_('COM_MODULES_EXPAND'); ?>:
					<a id="treeExpandAll" href="javascript://"><?php echo Text::_('JALL'); ?></a>,
					<a id="treeCollapseAll" href="javascript://"><?php echo Text::_('JNONE'); ?></a>
				</span>
				<input type="text" id="treeselectfilter" name="treeselectfilter" class="form-control search-query"
					autocomplete="off" placeholder="<?php echo Text::_('JSEARCH_FILTER'); ?>"
					aria-invalid="false" aria-label="<?php echo Text::_('JSEARCH_FILTER'); ?>">
			</div>
			<div class="card-body">
				<ul class="treeselect">
					<?php foreach ($menuTypes as &$type) : ?>
					<?php if (count($type->links)) : ?>
						<?php $prevlevel = 0; ?>
						<li>
							<div class="treeselect-item treeselect-header">
								<label class="nav-header"><?php echo $type->title; ?></label></div>
						<?php foreach ($type->links as $i => $link) : ?>
							<?php
							if ($prevlevel < $link->level)
							{
								echo '<ul class="treeselect-sub">';
							} elseif ($prevlevel > $link->level)
							{
								echo str_repeat('</li></ul>', $prevlevel - $link->level);
							} else {
								echo '</li>';
							}
							$selected = 0;
							if ($this->item->assignment == 0)
							{
								$selected = 1;
							} elseif ($this->item->assignment < 0)
							{
								$selected = in_array(-$link->value, $this->item->assigned);
							} elseif ($this->item->assignment > 0)
							{
								$selected = in_array($link->value, $this->item->assigned);
							}
							?>
								<li>
									<div class="treeselect-item">
										<?php
										$uselessMenuItem = in_array($link->type, array('separator', 'heading', 'alias', 'url'));
										?>
										<input type="checkbox" class="novalidate" name="jform[assigned][]" id="<?php echo $id . $link->value; ?>" value="<?php echo (int) $link->value; ?>"<?php echo $selected ? ' checked="checked"' : ''; echo $uselessMenuItem ? ' disabled="disabled"' : ''; ?>>
										<label for="<?php echo $id . $link->value; ?>" class="">
											<?php echo $link->text; ?> <span class="small"><?php echo Text::sprintf('JGLOBAL_LIST_ALIAS', $this->escape($link->alias)); ?></span>
											<?php if (Multilanguage::isEnabled() && $link->language != '' && $link->language != '*') : ?>
												<?php if ($link->language_image) : ?>
													<?php echo HTMLHelper::_('image', 'mod_languages/' . $link->language_image . '.gif', $link->language_title, array('title' => $link->language_title), true); ?>
												<?php else : ?>
													<?php echo '<span class="badge badge-secondary" title="' . $link->language_title . '">' . $link->language_sef . '</span>'; ?>
												<?php endif; ?>
											<?php endif; ?>
											<?php if ($link->published == 0) : ?>
												<?php echo ' <span class="badge badge-secondary">' . Text::_('JUNPUBLISHED') . '</span>'; ?>
											<?php endif; ?>
											<?php if ($uselessMenuItem) : ?>
												<?php echo ' <span class="badge badge-secondary">' . Text::_('COM_MODULES_MENU_ITEM_' . strtoupper($link->type)) . '</span>'; ?>
											<?php endif; ?>
										</label>
									</div>
							<?php

							if (!isset($type->links[$i + 1]))
							{
								echo str_repeat('</li></ul>', $link->level);
							}
							$prevlevel = $link->level;
							?>
							<?php endforeach; ?>
						</li>
						<?php endif; ?>
					<?php endforeach; ?>
				</ul>
				<joomla-alert id="noresultsfound" type="warning" class="hidden"><?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?></joomla-alert>
				<div class="hidden" id="treeselectmenu">
					<div class="nav-hover treeselect-menu">
						<div class="dropdown">
							<button type="button" data-toggle="dropdown" class="dropdown-toggle btn btn-sm btn-light">
								<span class="caret"></span>
								<span class="sr-only"><?php echo Text::sprintf('JGLOBAL_TOGGLE_DROPDOWN'); ?></span>
							</button>
							<div class="dropdown-menu">
								<h5 class="dropdown-header"><?php echo Text::_('COM_MODULES_SUBITEMS'); ?></h5>
								<div class="dropdown-divider"></div>
								<a class="dropdown-item checkall" href="javascript://"><span class="fas fa-check-square" aria-hidden="true"></span> <?php echo Text::_('JSELECT'); ?></a>
								<a class="dropdown-item uncheckall" href="javascript://"><span class="fas fa-square" aria-hidden="true"></span> <?php echo Text::_('COM_MODULES_DESELECT'); ?></a>
								<div class="treeselect-menu-expand">
									<div class="dropdown-divider"></div>
									<a class="dropdown-item expandall" href="javascript://"><span class="fas fa-plus" aria-hidden="true"></span> <?php echo Text::_('COM_MODULES_EXPAND'); ?></a>
									<a class="dropdown-item collapseall" href="javascript://"><span class="fas fa-minus" aria-hidden="true"></span> <?php echo Text::_('COM_MODULES_COLLAPSE'); ?></a>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php endif; ?>
	</div>
</div>
