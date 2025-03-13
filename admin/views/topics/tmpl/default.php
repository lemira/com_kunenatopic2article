<?php
defined('_JEXEC') or die;

JHtml::_('behavior.formvalidator');
JHtml::_('formbehavior.chosen', 'select');
?>

<form action="<?php echo JRoute::_('index.php?option=com_kunenatopic2article'); ?>" method="post" name="adminForm" id="adminForm" class="form-validate">
    <div class="container-fluid">
        <h1><?php echo JText::_('COM_KUNENATOPIC2ARTICLE_PARAMS_TITLE'); ?></h1>
        
        <div class="btn-toolbar mb-3">
            <button type="submit" class="btn btn-primary mr-2"><?php echo JText::_('COM_KUNENATOPIC2ARTICLE_REMEMBER'); ?></button>
            <a href="<?php echo JRoute::_('index.php?option=com_kunenatopic2article&task=reset'); ?>" class="btn btn-secondary mr-2"><?php echo JText::_('COM_KUNENATOPIC2ARTICLE_RESET_PARAMS'); ?></a>
            <a href="<?php echo JRoute::_('index.php?option=com_kunenatopic2article&task=create'); ?>" class="btn btn-success"><?php echo JText::_('COM_KUNENATOPIC2ARTICLE_CREATE_ARTICLES'); ?></a>
        </div>
        
        <h3><?php echo JText::_('COM_KUNENATOPIC2ARTICLE_ARTICLE_PARAMS'); ?></h3>
        <?php echo $this->form->renderFieldset('article_params'); ?>
        
        <h3><?php echo JText::_('COM_KUNENATOPIC2ARTICLE_POST_INFO'); ?></h3>
        <?php echo $this->form->renderFieldset('post_info'); ?>
        
        <input type="hidden" name="task" value="save" />
        <?php echo JHtml::_('form.token'); ?>
    </div>
</form>
