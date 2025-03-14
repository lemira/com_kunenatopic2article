<?php
defined('_JEXEC') or die;

JHtml::_('behavior.formvalidator');
JHtml::_('formbehavior.chosen', 'select');
?>

<form action="<?php echo JRoute::_('index.php?option=com_kunenatopic2article&task=save'); ?>" method="post" name="adminForm" id="adminForm" class="form-validate">
    <div class="container-fluid">
        <h1><?php echo JText::_('COM_KUNENATOPIC2ARTICLE_PARAMS_TITLE'); ?></h1>
        
        <div class="btn-toolbar mb-3">
            <button type="button" class="btn btn-primary mr-2" onclick="submitForm('save')">Remember</button>
            <button type="button" class="btn btn-secondary mr-2" onclick="submitForm('reset')">Reset Parameters</button>
            <a href="<?php echo JRoute::_('index.php?option=com_kunenatopic2article&task=create'); ?>" class="btn btn-success">Create Articles</a>
        </div>
        
        <h3><?php echo JText::_('COM_KUNENATOPIC2ARTICLE_ARTICLE_PARAMS'); ?></h3>
        <?php if ($this->form): ?>
            <?php echo $this->form->renderFieldset('article_params'); ?>
            <?php JFactory::getApplication()->enqueueMessage('Rendering article_params fieldset', 'notice'); ?>
        <?php else: ?>
            <?php JFactory::getApplication()->enqueueMessage('Form object is empty', 'error'); ?>
        <?php endif; ?>
        
        <h3><?php echo JText::_('COM_KUNENATOPIC2ARTICLE_POST_INFO'); ?></h3>
        <?php if ($this->form): ?>
            <?php echo $this->form->renderFieldset('post_info'); ?>
            <?php JFactory::getApplication()->enqueueMessage('Rendering post_info fieldset', 'notice'); ?>
        <?php else: ?>
            <?php JFactory::getApplication()->enqueueMessage('Form object is empty', 'error'); ?>
        <?php endif; ?>
        
        <input type="hidden" name="task" id="task" />
        <?php echo JHtml::_('form.token'); ?>
    </div>
</form>

<script type="text/javascript">
    console.log('Script loaded');
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM loaded');
        var form = document.getElementById('adminForm');
        if (form) {
            console.log('Form found');
        } else {
            console.log('Form not found');
        }

        window.submitForm = function(task) {
            console.log('Submitting form with task: ' + task);
            var form = document.getElementById('adminForm');
            if (document.formvalidator.isValid(form)) {
                console.log('Form is valid');
                document.getElementById('task').value = task;
                form.submit();
            } else {
                console.log('Form validation failed');
                alert('Please check the form fields');
            }
        };
    });
</script>
