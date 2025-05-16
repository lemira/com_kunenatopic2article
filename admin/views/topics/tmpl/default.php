<?php
defined('_JEXEC') or die;

JHtml::_('behavior.formvalidator');
JHtml::_('formbehavior.chosen', 'select');
?>

<form action="<?php echo JRoute::_('index.php?option=com_kunenatopic2article&view=topics&task=topic.save'); ?>" method="post" name="adminForm" id="adminForm" class="form-validate">
    <div class="container-fluid">
        <h1><?php echo JText::_('COM_KUNENATOPIC2ARTICLE_PARAMS_TITLE'); ?></h1>
        
        <div class="btn-toolbar mb-3">
            <button type="button" class="btn btn-primary mr-2" onclick="Joomla.submitbutton('topic.save')"><?php echo JText::_('COM_KUNENATOPIC2ARTICLE_BUTTON_REMEMBER'); ?></button>
            <button type="button" class="btn btn-secondary mr-2" onclick="Joomla.submitbutton('reset')"><?php echo JText::_('COM_KUNENATOPIC2ARTICLE_BUTTON_RESET'); ?></button>
            <a href="<?php echo JRoute::_('index.php?option=com_kunenatopic2article&task=create'); ?>" class="btn btn-success"><?php echo JText::_('COM_KUNENATOPIC2ARTICLE_BUTTON_CREATE'); ?></a>
        </div>
        
        <h3><?php echo JText::_('COM_KUNENATOPIC2ARTICLE_ARTICLE_PARAMS'); ?></h3>
        <?php if ($this->form): ?> 
            <?php echo $this->form->renderFieldset('article_params'); ?>
           <?php else: ?>
            <?php JFactory::getApplication()->enqueueMessage('Form object is empty', 'error'); ?>
        <?php endif; ?>
        
        <h3><?php echo JText::_('COM_KUNENATOPIC2ARTICLE_POST_INFO'); ?></h3>
        <?php if ($this->form): ?>
            <?php echo $this->form->renderFieldset('post_info'); ?>
          <?php else: ?>
            <?php JFactory::getApplication()->enqueueMessage('Form object is empty', 'error'); ?>
        <?php endif; ?>
        
        <input type="hidden" name="task" value="" />
        <?php echo JHtml::_('form.token'); ?>
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
            alert('Please check the form fields');
        }
    };
</script>
