<?php
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('formbehavior.chosen', 'select');
?>

<form action="<?php echo JRoute::_('index.php?option=com_kunenatopic2article&view=topics'); ?>" method="post" name="adminForm" id="adminForm" class="form-validate">
    <div class="container-fluid">
        <h1><?php echo Text::_('COM_KUNENATOPIC2ARTICLE_PARAMS_TITLE'); ?></h1>
        
        <div class="btn-toolbar mb-3">
            <button type="button" class="btn btn-primary mr-2" onclick="Joomla.submitbutton('topic.save')"><?php echo Text::_('COM_KUNENATOPIC2ARTICLE_BUTTON_REMEMBER'); ?></button>
            <button type="button" class="btn btn-secondary mr-2" onclick="Joomla.submitbutton('topic.reset')"><?php echo Text::_('COM_KUNENATOPIC2ARTICLE_BUTTON_RESET'); ?></button>
            <button type="button" class="btn btn-success" onclick="Joomla.submitbutton('article.create')"><?php echo Text::_('COM_KUNENATOPIC2ARTICLE_BUTTON_CREATE'); ?></button>
        </div>
        
        <h3><?php echo Text::_('COM_KUNENATOPIC2ARTICLE_ARTICLE_PARAMS'); ?></h3>
        <?php if ($this->form): ?> 
            <?php echo $this->form->renderFieldset('article_params'); ?>
        <?php else: ?>
            <?php Factory::getApplication()->enqueueMessage(Text::_('COM_KUNENATOPIC2ARTICLE_FORM_OBJECT_EMPTY'), 'error'); ?>
        <?php endif; ?>
        
        <h3><?php echo Text::_('COM_KUNENATOPIC2ARTICLE_POST_INFO'); ?></h3>
        <?php if ($this->form): ?>
            <?php echo $this->form->renderFieldset('post_info'); ?>
        <?php else: ?>
            <?php Factory::getApplication()->enqueueMessage(Text::_('COM_KUNENATOPIC2ARTICLE_FORM_OBJECT_EMPTY'), 'error'); ?>
        <?php endif; ?>
        
        <input type="hidden" name="task" value="" />
        <?php echo HTMLHelper::_('form.token'); ?>
    </div>
</form>

<script type="text/javascript">
    Joomla.submitbutton = function(task) {
        console.log('Submitting form with task: ' + task);
        var form = document.getElementById('adminForm');
        if (document.formvalidator.isValid(form)) {
            console.log('Form is valid');
            Joomla.submitform(task, form);
        } else {
            console.log('Form validation failed');
            alert('<?php echo Text::_('COM_KUNENATOPIC2ARTICLE_FORM_VALIDATION_FAILED'); ?>');
        }
    };
</script>
