<?php
defined('_JEXEC') or die('Restricted access');

JHtml::_('behavior.formvalidation');
JHtml::_('formbehavior.chosen', 'select');
?>
<form action="index.php?option=com_kunenatopic2article&task=kunenatopic2article.save" method="post" id="adminForm" class="form-validate">
    <div class="row-fluid">
        <div class="span12">
            <fieldset class="adminform">
                <legend><?php echo JText::_('COM_KUNENATOPIC2ARTICLE_ARTICLE_FORM'); ?></legend>
                <div class="control-group">
                    <div class="control-label">
                        <?php echo $this->form->getLabel('topic_id'); ?>
                    </div>
                    <div class="controls">
                        <?php echo $this->form->getInput('topic_id'); ?>
                    </div>
                </div>
                <div class="control-group">
                    <div class="control-label">
                        <?php echo $this->form->getLabel('title'); ?>
                    </div>
                    <div class="controls">
                        <?php echo $this->form->getInput('title'); ?>
                    </div>
                </div>
                <div class="control-group">
                    <div class="control-label">
                        <?php echo $this->form->getLabel('content'); ?>
                    </div>
                    <div class="controls">
                        <?php echo $this->form->getInput('content'); ?>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary"><?php echo JText::_('JSUBMIT'); ?></button>
                <input type="hidden" name="task" value="kunenatopic2article.save" />
                <?php echo JHtml::_('form.token'); ?>
            </fieldset>
        </div>
    </div>
</form>
