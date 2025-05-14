<?php
defined('_JEXEC') or die;

class KunenaTopic2ArticleControllerTopic extends JControllerForm
{
    public function test()
    {
        $model = $this->getModel('Topic');
        $table = $model->getTable();
        $table->load(1); // Загружаем запись с id=1
        var_dump($table);
        die;
    }
}
