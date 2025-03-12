<?php
defined('_JEXEC') or die;

JFactory::getApplication()->enqueueMessage('Debug: params value: ' . print_r($this->params, true), 'message');

if ($this->params) {
    JFactory::getApplication()->enqueueMessage('Parameters loaded: ' . htmlspecialchars($this->params->topic_selection), 'message');
} else {
    JFactory::getApplication()->enqueueMessage('No parameters available.', 'warning');
}

// Временная отладка: выводим параметры напрямую
if ($this->params) {
    echo '<h3>Debug Parameters</h3>';
    echo '<pre>';
    echo 'topic_selection: ' . htmlspecialchars($this->params->topic_selection) . '<br>';
    echo 'article_category: ' . htmlspecialchars($this->params->article_category) . '<br>';
    echo 'post_transfer_scheme: ' . htmlspecialchars($this->params->post_transfer_scheme) . '<br>';
    echo 'max_article_size: ' . htmlspecialchars($this->params->max_article_size) . '<br>';
    echo '</pre>';
} else {
    echo '<p>No parameters to display.</p>';
}

// Закомментируем старую логику, чтобы избежать ошибок
/*
Оставь здесь старую логику шаблона, начиная с <?php до конца файла





<?php
defined('_JEXEC') or die('Restricted access');

JFactory::getApplication()->enqueueMessage('Debug: params value: ' . print_r($this->params, true), 'message');

if ($this->params) {
    JFactory::getApplication()->enqueueMessage('Parameters loaded: ' . htmlspecialchars($this->params->topic_selection), 'message');
} else {
    JFactory::getApplication()->enqueueMessage('No parameters available.', 'warning');
}

JHtml::_('bootstrap.tooltip');
JHtml::_('behavior.multiselect');
JHtml::_('formbehavior.chosen', 'select');

$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
?>
<form action="<?php echo JRoute::_('index.php?option=com_kunenatopic2article&view=topics'); ?>" method="post" name="adminForm" id="adminForm">
    <div class="row">
        <div class="col-md-12">
            <h3><?php echo JText::_('COM_KUNENATOPIC2ARTICLE_APP_PARAMETERS'); ?></h3>
            <table class="table table-striped" id="topicList">
                <thead>
                    <tr>
                        <th width="1%">
                            <?php echo JHtml::_('grid.checkall'); ?>
                        </th>
                        <th>
                            <?php echo JHtml::_('grid.sort', 'COM_KUNENATOPIC2ARTICLE_TOPIC_SELECTION', 'topic_id', $listDirn, $listOrder); ?>
                        </th>
                        <th>
                            <?php echo JHtml::_('grid.sort', 'COM_KUNENATOPIC2ARTICLE_ARTICLE_CATEGORY', 'kunena_category_id', $listDirn, $listOrder); ?>
                        </th>
                        <th>
                            <?php echo JHtml::_('grid.sort', 'COM_KUNENATOPIC2ARTICLE_MAX_ARTICLE_SIZE', 'max_article_size', $listDirn, $listOrder); ?>
                        </th>
                        <th>
                            <?php echo JHtml::_('grid.sort', 'COM_KUNENATOPIC2ARTICLE_TRANSFER_SCHEME', 'transfer_scheme', $listDirn, $listOrder); ?>
                        </th>
                        <th>
                            <?php echo JHtml::_('grid.sort', 'COM_KUNENATOPIC2ARTICLE_IGNORED_AUTHORS', 'ignored_authors', $listDirn, $listOrder); ?>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($this->items)) : ?>
                        <?php foreach ($this->items as $i => $item) : ?>
                            <tr class="row<?php echo $i % 2; ?>">
                                <td><?php echo JHtml::_('grid.id', $i, $item->id); ?></td>
                                <td><?php echo $this->escape($item->topic_id); ?></td>
                                <td><?php echo $this->escape($item->kunena_category_id); ?></td>
                                <td><?php echo $this->escape($item->max_article_size); ?></td>
                                <td><?php echo $this->escape($item->transfer_scheme); ?></td>
                                <td><?php echo $this->escape($item->ignored_authors); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="6"><?php echo JText::_('COM_KUNENATOPIC2ARTICLE_NO_ITEMS'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <h3><?php echo JText::_('COM_KUNENATOPIC2ARTICLE_POST_INFO'); ?></h3>
            <table class="table table-striped" id="postList">
                <thead>
                    <tr>
                        <th width="1%">
                            <?php echo JHtml::_('grid.checkall'); ?>
                        </th>
                        <th>
                            <?php echo JHtml::_('grid.sort', 'COM_KUNENATOPIC2ARTICLE_POST_AUTHOR', 'post_author', $listDirn, $listOrder); ?>
                        </th>
                        <th>
                            <?php echo JHtml::_('grid.sort', 'COM_KUNENATOPIC2ARTICLE_CREATION_DATE', 'creation_date', $listDirn, $listOrder); ?>
                        </th>
                        <th>
                            <?php echo JHtml::_('grid.sort', 'COM_KUNENATOPIC2ARTICLE_CREATION_TIME', 'creation_time', $listDirn, $listOrder); ?>
                        </th>
                        <th>
                            <?php echo JHtml::_('grid.sort', 'COM_KUNENATOPIC2ARTICLE_POST_ID_LINK', 'post_id_link', $listDirn, $listOrder); ?>
                        </th>
                        <th>
                            <?php echo JHtml::_('grid.sort', 'COM_KUNENATOPIC2ARTICLE_POST_TITLE', 'post_title', $listDirn, $listOrder); ?>
                        </th>
                        <th>
                            <?php echo JHtml::_('grid.sort', 'COM_KUNENATOPIC2ARTICLE_FORUM_POST_LINK', 'forum_post_link', $listDirn, $listOrder); ?>
                        </th>
                        <th>
                            <?php echo JHtml::_('grid.sort', 'COM_KUNENATOPIC2ARTICLE_REMINDER_LINE_COUNT', 'reminder_line_count', $listDirn, $listOrder); ?>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($this->items)) : ?>
                        <?php foreach ($this->items as $i => $item) : ?>
                            <tr class="row<?php echo $i % 2; ?>">
                                <td><?php echo JHtml::_('grid.id', $i, $item->id); ?></td>
                                <td><?php echo $this->escape($item->post_author); ?></td>
                                <td><?php echo $this->escape($item->creation_date); ?></td>
                                <td><?php echo $this->escape($item->creation_time); ?></td>
                                <td><?php echo $this->escape($item->post_id_link); ?></td>
                                <td><?php echo $this->escape($item->post_title); ?></td>
                                <td><?php echo $this->escape($item->forum_post_link); ?></td>
                                <td><?php echo $this->escape($item->reminder_line_count); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="8"><?php echo JText::_('COM_KUNENATOPIC2ARTICLE_NO_ITEMS'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <input type="hidden" name="task" value="" />
    <input type="hidden" name="boxchecked" value="0" />
    <?php echo JHtml::_('form.token'); ?>
</form>
*/
