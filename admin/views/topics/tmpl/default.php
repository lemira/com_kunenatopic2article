<?php
defined('_JEXEC') or die('Restricted access');

$logFile = JPATH_BASE . '/logs/template_debug.log';
$message = "Loading default template at " . date('Y-m-d H:i:s') . "\n";
file_put_contents($logFile, $message, FILE_APPEND);

$form = $this->form;
if ($form) {
    ?>
    <div class="kunenatopic2article-form">
        <h1>Kunena Topic to Article</h1>
        <form action="<?php echo JRoute::_('index.php?option=com_kunenatopic2article'); ?>" method="post" name="adminForm" id="adminForm">
            <div class="form-group">
                <?php echo $form->renderField('topic_id'); ?>
                <?php echo $form->renderField('article_title'); ?>
                <?php echo $form->renderField('article_content'); ?>
            </div>
            <button type="submit" class="btn btn-primary">Save</button>
            <input type="hidden" name="task" value="kunenatopic2article.save" />
            <?php echo JHtml::_('form.token'); ?>
        </form>
    </div>
    <?php
} else {
    echo "<p>Form is not loaded.</p>";
}
