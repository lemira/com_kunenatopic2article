<?php
defined('_JEXEC') or die;

class KunenaTopic2ArticleModelKunenaTopic2Article extends JModelAdmin
{
    public function getTable($type = 'KunenaTopic2Article', $prefix = 'KunenaTopic2ArticleTable', $config = array())
    {
        return JTable::getInstance($type, $prefix, $config);
    }

    public function getForm($data = array(), $loadData = true)
    {
        $form = $this->loadForm('com_kunenatopic2article.kunenatopic2article', 'kunenatopic2article', array('control' => 'jform', 'load_data' => $loadData));
        if (empty($form)) {
            return false;
        }
        return $form;
    }

    protected function loadFormData()
    {
        $data = $this->getParams();
        return $data;
    }

    public function getParams()
    {
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $query->select('*')
              ->from($db->quoteName('#__kunenatopic2article_params'))
              ->where($db->quoteName('id') . ' = 1');
        $db->setQuery($query);
        return $db->loadObject();
    }
}
