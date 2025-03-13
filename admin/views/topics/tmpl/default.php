<?php
defined('_JEXEC') or die;

JHtml::_('bootstrap.tooltip');
JHtml::_('behavior.formvalidator');
?>

<div class="container-fluid">
    <h1><?php echo JText::_('COM_KUNENATOPIC2ARTICLE_PARAMS_TITLE'); ?></h1>
    
    <div class="btn-toolbar" role="toolbar">
        <div class="btn-group mr-2">
            <a class="btn btn-primary" href="<?php echo JRoute::_('index.php?option=com_kunenatopic2article&task=edit'); ?>">
                <?php echo JText::_('JTOOLBAR_EDIT'); ?>
            </a>
        </div>
        <div class="btn-group mr-2">
            <a class="btn btn-secondary" href="<?php echo JRoute::_('index.php?option=com_kunenatopic2article&task=reset'); ?>">
                <?php echo JText::_('COM_KUNENATOPIC2ARTICLE_RESET_PARAMS'); ?>
            </a>
        </div>
        <div class="btn-group">
            <a class="btn btn-success" href="<?php echo JRoute::_('index.php?option=com_kunenatopic2article&task=create'); ?>">
                <?php echo JText::_('COM_KUNENATOPIC2ARTICLE_CREATE_ARTICLES'); ?>
            </a>
        </div>
    </div>
    
    <?php if ($this->params): ?>
        <table class="table table-striped mt-3">
            <thead>
                <tr>
                    <th><?php echo JText::_('COM_KUNENATOPIC2ARTICLE_PARAM_NAME'); ?></th>
                    <th><?php echo JText::_('COM_KUNENATOPIC2ARTICLE_PARAM_VALUE'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Topic Selection</td>
                    <td><?php echo htmlspecialchars($this->params->topic_selection); ?></td>
                </tr>
                <tr>
                    <td>Article Category</td>
                    <td><?php echo htmlspecialchars($this->params->article_category); ?></td>
                </tr>
                <tr>
                    <td>Post Transfer Scheme</td>
                    <td><?php echo htmlspecialchars($this->params->post_transfer_scheme); ?></td>
                </tr>
                <tr>
                    <td>Max Article Size</td>
                    <td><?php echo htmlspecialchars($this->params->max_article_size); ?></td>
                </tr>
                <tr>
                    <td>Post Author</td>
                    <td><?php echo htmlspecialchars($this->params->post_author); ?></td>
                </tr>
                <tr>
                    <td>Post Creation Date</td>
                    <td><?php echo htmlspecialchars($this->params->post_creation_date); ?></td>
                </tr>
                <tr>
                    <td>Post Creation Time</td>
                    <td><?php echo htmlspecialchars($this->params->post_creation_time); ?></td>
                </tr>
                <tr>
                    <td>Post IDs</td>
                    <td><?php echo htmlspecialchars($this->params->post_ids); ?></td>
                </tr>
                <tr>
                    <td>Post Title</td>
                    <td><?php echo htmlspecialchars($this->params->post_title); ?></td>
                </tr>
                <tr>
                    <td>Kunena Post Link</td>
                    <td><?php echo htmlspecialchars($this->params->kunena_post_link); ?></td>
                </tr>
                <tr>
                    <td>Reminder Lines</td>
                    <td><?php echo htmlspecialchars($this->params->reminder_lines); ?></td>
                </tr>
                <tr>
                    <td>Ignored Authors</td>
                    <td><?php echo htmlspecialchars($this->params->ignored_authors ?: 'None'); ?></td>
                </tr>
            </tbody>
        </table>
    <?php else: ?>
        <div class="alert alert-warning mt-3">
            <?php echo JText::_('COM_KUNENATOPIC2ARTICLE_NO_PARAMS_FOUND'); ?>
        </div>
    <?php endif; ?>
</div>
