<?php
defined('_JEXEC') or die;

class KunenaTopic2ArticleModelTopic extends JModelAdmin
{
    protected $state;

    public function getTable($type = 'Topic', $prefix = 'KunenaTopic2ArticleTable', $config = array())
    {
        return JTable::getInstance($type, $prefix, $config);
    }

    public function getForm($data = array(), $loadData = true)
    {
        $form = $this->loadForm('com_kunenatopic2article.topic', 'topic', array('control' => 'jform', 'load_data' => $loadData));
        if (empty($form)) {
            JFactory::getApplication()->enqueueMessage('Form not found', 'error');
            return false;
        }
        return $form;
    }

    protected function loadFormData()
    {
        $data = JFactory::getApplication()->getUserState('com_kunenatopic2article.edit.topic.data', array());
        if (empty($data)) {
            $data = $this->getItem();
        }
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
        $result = $db->loadObject();
        if (!$result) {
            JFactory::getApplication()->enqueueMessage('No params found in database', 'warning');
        }
        return $result;
    }

    protected function populateState()
    {
        $app = JFactory::getApplication();

        // Load state from the request
        $ordering = $app->input->get('filter_order', 'id');
        $direction = $app->input->get('filter_order_Dir', 'asc');
        
        $this->setState('list.ordering', $ordering);
        $this->setState('list.direction', $direction);
    }

    public function getState($property = null, $default = null)
    {
        if (!$this->state) {
            $this->state = new JObject();
            $this->populateState();
        }
        return parent::getState($property, $default);
    }
}
