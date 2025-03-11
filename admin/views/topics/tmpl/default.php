<?php
defined('_JEXEC') or die('Restricted access');

JHtml::_('bootstrap.tooltip');
JHtml::_('behavior.multiselect');
JHtml::_('formbehavior.chosen', 'select');

$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
?>
<form action="<?php echo JRoute::_('index.php?option=com_kunenatopic2article&view=topics'); ?>" method="post" name="adminForm" id="adminForm">
    <div class="row">
        <div class="col-md-12">
            <table class="table table-striped" id="topicList">
                <thead>
                    <tr>
                        <th width="1%">
                            <?php echo JHtml::_('grid.checkall'); ?>
                        </th>
                        <th width="20%">
                            <?php echo JHtml::_('grid.sort', 'COM_KUNENATOPIC2ARTICLE_TOPIC_ID', 'topic_id', $listDirn, $listOrder); ?>
                        </th>
                        <?php
                        // Добавь другие заголовки колонок в зависимости от структуры таблицы
                        $columns = array('param1', 'param2'); // Замени на реальные имена полей из таблицы
                        foreach ($columns as $column) {
                            echo '<th>' . JHtml::_('grid.sort', 'COM_KUNENATOPIC2ARTICLE_' . strtoupper($column), $column, $listDirn, $listOrder) . '</th>';
                        }
                        ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($this->items)) : ?>
                        <?php foreach ($this->items as $i => $item) : ?>
                            <tr class="row<?php echo $i % 2; ?>">
                                <td><?php echo JHtml::_('grid.id', $i, $item->id); ?></td>
                                <td><?php echo $item->topic_id; ?></td>
                                <?php
                                foreach ($columns as $column) {
                                    echo '<td>' . $this->escape($item->$column) . '</td>';
                                }
                                ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="3"><?php echo JText::_('COM_KUNENATOPIC2ARTICLE_NO_ITEMS'); ?></td>
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
