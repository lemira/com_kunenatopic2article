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
        $form = $this->loadForm('com_kunenatopic2article.params', 'params', array('control' => 'jform', 'load_data' => $loadData));
        if (empty($form)) {
            JFactory::getApplication()->enqueueMessage('Form not found', 'error');
            return false;
        }
        
        if ($loadData) {
            $data = $this->loadFormData();
            $data->topic_selection = 0;
            $form->bind($data);
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
        $result = $db->loadObject();
        if (!$result) {
            JFactory::getApplication()->enqueueMessage('No params found in database', 'warning');
        }
        return $result;
    }

    public function save($data)
    {
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        
        JFactory::getApplication()->enqueueMessage('Saving data: ' . print_r($data, true), 'message');
        
        $fields = array(
            $db->quoteName('topic_selection') . ' = ' . (int)$data['topic_selection'],
            $db->quoteName('article_category') . ' = ' . (int)$data['article_category'],
            $db->quoteName('post_transfer_scheme') . ' = ' . (int)$data['post_transfer_scheme'],
            $db->quoteName('max_article_size') . ' = ' . (int)$data['max_article_size'],
            $db->quoteName('post_author') . ' = ' . (int)$data['post_author'],
            $db->quoteName('post_creation_date') . ' = ' . $db->quote($data['post_creation_date']),
            $db->quoteName('post_creation_time') . ' = ' . $db->quote($data['post_creation_time']),
            $db->quoteName('post_ids') . ' = ' . (int)$data['post_ids'],
            $db->quoteName('post_title') . ' = ' . (int)$data['post_title'],
            $db->quoteName('kunena_post_link') . ' = ' . (int)$data['kunena_post_link'],
            $db->quoteName('reminder_lines') . ' = ' . (int)$data['reminder_lines'],
            $db->quoteName('ignored_authors') . ' = ' . $db->quote($data['ignored_authors'])
        );
        
        $query->update($db->quoteName('#__kunenatopic2article_params'))
              ->set($fields)
              ->where($db->quoteName('id') . ' = 1');
        
        $db->setQuery($query);
        
        JFactory::getApplication()->enqueueMessage('SQL Query: ' . (string)$query, 'message');
        
        try {
            $db->execute();
            JFactory::getApplication()->enqueueMessage('Database updated successfully', 'message');
            return true;
        } catch (Exception $e) {
            JFactory::getApplication()->enqueueMessage('Error saving params: ' . $e->getMessage(), 'error');
            return false;
        }
    }

    public function reset()
    {
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        
        $fields = array(
            $db->quoteName('topic_selection') . ' = 0',
            $db->quoteName('article_category') . ' = 0',
            $db->quoteName('post_transfer_scheme') . ' = 1',
            $db->quoteName('max_article_size') . ' = 40000',
            $db->quoteName('post_author') . ' = 1',
            $db->quoteName('post_creation_date') . ' = ' . $db->quote(date('Y-m-d H:i:s')),
            $db->quoteName('post_creation_time') . ' = ' . $db->quote(date('Y-m-d H:i:s')),
            $db->quoteName('post_ids') . ' = 1',
            $db->quoteName('post_title') . ' = 0',
            $db->quoteName('kunena_post_link') . ' = 0',
            $db->quoteName('reminder_lines') . ' = 0',
            $db->quoteName('ignored_authors') . ' = NULL'
        );
        
        $query->update($db->quoteName('#__kunenatopic2article_params'))
              ->set($fields)
              ->where($db->quoteName('id') . ' = 1');
        
        $db->setQuery($query);
        
        try {
            $db->execute();
            JFactory::getApplication()->enqueueMessage('Database reset successfully', 'message');
            return true;
        } catch (Exception $e) {
            JFactory::getApplication()->enqueueMessage('Error resetting params: ' . $e->getMessage(), 'error');
            return false;
        }
    }

    protected function populateState()
    {
        $app = JFactory::getApplication();
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
