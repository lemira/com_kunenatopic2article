<?php
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.modeladmin');

class KunenaTopic2ArticleModelKunenaTopic2Article extends JModelAdmin
{
    public function getForm($data = [], $loadData = true)
    {
        $form = $this->loadForm('com_kunenatopic2article.kunenatopic2article', 'kunenatopic2article', ['control' => 'jform', 'load_data' => $loadData]);
        if (empty($form)) {
            return false;
        }
        return $form;
    }

    public function save($data)
    {
        $table = $this->getTable();
        $table->topic_id = $data['topic_id'];
        $table->title = $data['title'];
        $table->content = $data['content'];
        return $table->store();
    }

    public function getTable($type = 'KunenaTopic2Article', $prefix = 'KunenaTopic2ArticleTable', $config = [])
    {
        return JTable::getInstance($type, $prefix, $config);
    }
}
