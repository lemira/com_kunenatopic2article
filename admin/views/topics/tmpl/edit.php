<?php
defined('_JEXEC') or die;

JHtml::_('behavior.formvalidator');
JHtml::_('formbehavior.chosen', 'select');
?>

<form action="<?php echo JRoute::_('index.php?option=com_kunenatopic2article'); ?>" method="post" name="adminForm" id="adminForm" class="form-validate">
    <div class="container-fluid">
        <h1><?php echo JText::_('COM_KUNENATOPIC2ARTICLE_PARAMS_TITLE'); ?></h1>
        
        <h3><?php echo JText::_('COM_KUNENATOPIC2ARTICLE_ARTICLE_PARAMS'); ?></h3>
        <?php echo $this->form->renderFieldset('article_params'); ?>
        
        <h3><?php echo JText::_('COM_KUNENATOPIC2ARTICLE_POST_INFO'); ?></h3>
        <?php echo $this->form->renderFieldset('post_info'); ?>
        
        <div class="btn-toolbar mt-3">
            <button type="submit" class="btn btn-success"><?php echo JText::_('JSUBMIT'); ?></button>
            <a href="<?php echo JRoute::_('index.php?option=com_kunenatopic2article'); ?>" class="btn btn-secondary"><?php echo JText::_('JCANCEL'); ?></a>
        </div>
        
        <input type="hidden" name="task" value="save" />
        <?php echo JHtml::_('form.token'); ?>
    </div>
</form>
