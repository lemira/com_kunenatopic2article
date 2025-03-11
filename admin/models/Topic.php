<?php
defined('_JEXEC') or die;

class KunenaTopic2ArticleModelTopic extends JModelAdmin
{
    protected function getTable($type = 'Topic', $prefix = 'KunenaTopic2ArticleTable', $config = array())
    {
        return JTable::getInstance($type, $prefix, $config);
    }

    public function getForm($data = array(), $loadData = true)
    {
        $form = $this->loadForm('com_kunenatopic2article.topic', 'topic', array('control' => 'jform', 'load_data' => $loadData));
        if (empty($form))
        {
            return false;
        }
        return $form;
    }

    protected function loadFormData()
    {
        $data = JFactory::getApplication()->getUserState('com_kunenatopic2article.edit.topic.data', array());
        if (empty($data))
        {
            $data = $this->getItem();
        }
        return $data;
    }

    // Если тут были другие методы, оставь их
}
