<?php
/**
 * @package     Kunena Topic to Article
 * @subpackage  com_kunenatopic2article
 * @version     1.0.0
 * @copyright   Copyright (C) 2025 lr. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $this->document->getWebAssetManager();
$wa->useScript('keepalive')
   ->useScript('form.validate');

// Get current parameters remembered status
$paramsRemembered = $this->get('Model')->getParamsRemembered();
?>

<form action="<?php echo Route::_('index.php?option=com_kunenatopic2article&view=topic'); ?>" method="post" name="adminForm" id="adminForm" class="form-validate">
    
    <div class="row">
        <div class="col-lg-9">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-6">
                            <?php echo $this->form->renderField('topic_id'); ?>
                            <?php echo $this->form->renderField('article_category'); ?>
                            <?php echo $this->form->renderField('max_article_size'); ?>
                            <?php echo $this->form->renderField('post_transfer_scheme'); ?>
                            <?php echo $this->form->renderField('ignored_authors'); ?>
                            <?php echo $this->form->renderField('reminder_lines'); ?>
                        </div>
                        <div class="col-lg-6">
                            <fieldset class="options-form">
                                <legend><?php echo Text::_('COM_KUNENATOPIC2ARTICLE_POST_INFO'); ?></legend>
                                <?php echo $this->form->renderField('post_author'); ?>
                                <?php echo $this->form->renderField('post_creation_date'); ?>
                                <?php echo $this->form->renderField('post_creation_time'); ?>
                                <?php echo $this->form->renderField('post_ids'); ?>
                                <?php echo $this->form->renderField('post_title'); ?>
                                <?php echo $this->form->renderField('kunena_post_link'); ?>
                            </fieldset>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-primary" onclick="Joomla.submitbutton('remember')">
                            <?php echo Text::_('COM_KUNENATOPIC2ARTICLE_BUTTON_REMEMBER'); ?>
                        </button>
                        
                        <button type="button" class="btn btn-warning" onclick="Joomla.submitbutton('reset')">
                            <?php echo Text::_('COM_KUNENATOPIC2ARTICLE_BUTTON_RESET'); ?>
                        </button>
                        
                        <button type="button" class="btn btn-success" onclick="Joomla.submitbutton('createArticles')" 
                                <?php echo $paramsRemembered ? '' : 'disabled'; ?>>
                            <?php echo Text::_('COM_KUNENATOPIC2ARTICLE_BUTTON_CREATE'); ?>
                        </button>
                    </div>
                    
                    <?php if ($paramsRemembered): ?>
                        <div class="alert alert-info mt-3">
                            <small><?php echo Text::_('COM_KUNENATOPIC2ARTICLE_PARAMS_REMEMBERED'); ?></small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <input type="hidden" name="task" value="" />
    <?php echo HTMLHelper::_('form.token'); ?>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Form validation before remember action
    const rememberBtn = document.querySelector('button[onclick*="remember"]');
    const resetBtn = document.querySelector('button[onclick*="reset"]');
    const createBtn = document.querySelector('button[onclick*="createArticles"]');
    
    if (rememberBtn) {
        rememberBtn.addEventListener('click', function(e) {
            if (!validateRequiredFields()) {
                e.preventDefault();
                alert('<?php echo Text::_('COM_KUNENATOPIC2ARTICLE_FORM_VALIDATION_FAILED'); ?>');
                return false;
            }
        });
    }
    
    if (resetBtn) {
        resetBtn.addEventListener('click', function(e) {
            if (!confirm('<?php echo Text::_('COM_KUNENATOPIC2ARTICLE_CONFIRM_RESET'); ?>')) {
                e.preventDefault();
                return false;
            }
        });
    }
    
    if (createBtn) {
        createBtn.addEventListener('click', function(e) {
            if (!confirm('<?php echo Text::_('COM_KUNENATOPIC2ARTICLE_CONFIRM_CREATE'); ?>')) {
                e.preventDefault();
                return false;
            }
        });
    }
    
    function validateRequiredFields() {
        const topicId = document.getElementById('jform_topic_id');
        const category = document.getElementById('jform_article_category');
        
        if (!topicId || !topicId.value.trim()) {
            topicId.focus();
            return false;
        }
        
        if (!category || !category.value) {
            category.focus();
            return false;
        }
        
        return true;
    }
});

// Joomla submitbutton function
Joomla.submitbutton = function(task) {
    const form = document.getElementById('adminForm');
    
    if (task === 'remember') {
        if (!document.formvalidator.isValid(form)) {
            alert('<?php echo Text::_('COM_KUNENATOPIC2ARTICLE_FORM_VALIDATION_FAILED'); ?>');
            return false;
        }
    }
    
    Joomla.submitform(task, form);
};
</script>
