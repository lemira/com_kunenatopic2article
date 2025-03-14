<?php
defined('_JEXEC') or die;

class KunenaTopic2ArticleModelTopic extends JModelAdmin
{
    public function getTable($type = 'Params', $prefix = 'KunenaTopic2ArticleTable', $config = array())
    {
        $tableFile = JPath::clean(JPATH_ADMINISTRATOR . '/components/com_kunenatopic2article/tables/Params.php');
        if (!file_exists($tableFile)) {
            JFactory::getApplication()->enqueueMessage('Table file ' . $tableFile . ' not found', 'error');
        } else {
            JFactory::getApplication()->enqueueMessage('Table file ' . $tableFile . ' found', 'notice');
        }

        $table = JTable::getInstance($type, $prefix, $config);
        if ($table === false) {
            JFactory::getApplication()->enqueueMessage('Failed to load table ' . $prefix . $type, 'error');
            return JTable::getInstance('Content');
        }

        return $table;
    }

    public function getForm($data = array(), $loadData = true)
    {
        JFactory::getApplication()->enqueueMessage('Trying to load form com_kunenatopic2article.topic', 'notice');
        $form = $this->loadForm('com_kunenatopic2article.topic', 'topic', array('control' => 'jform', 'load_data' => $loadData));
        if (empty($form)) {
            JFactory::getApplication()->enqueueMessage('Failed to load form com_kunenatopic2article.topic', 'error');
            return false;
        }

        JFactory::getApplication()->enqueueMessage('Form com_kunenatopic2article.topic loaded successfully', 'notice');
        return $form;
    }

    protected function loadFormData()
    {
        JFactory::getApplication()->enqueueMessage('Loading form data', 'notice');
        $data = JFactory::getApplication()->getUserState('com_kunenatopic2article.edit.topic.data', array());

        if (empty($data)) {
            $params = $this->getParams();
            // Форматируем данные для формы
            $data = array(
                'topic_selection' => isset($params['topic_selection']) ? $params['topic_selection'] : 0,
                'article_category' => isset($params['article_category']) ? $params['article_category'] : 0,
                'post_transfer_scheme' => isset($params['post_transfer_scheme']) ? $params['post_transfer_scheme'] : 1,
                'max_article_size' => isset($params['max_article_size']) ? $params['max_article_size'] : 40000,
                'post_author' => isset($params['post_author']) ? $params['post_author'] : 1,
                'post_creation_date' => isset($params['post_creation_date']) ? $params['post_creation_date'] : date('Y-m-d'),
                'post_creation_time' => isset($params['post_creation_time']) ? $params['post_creation_time'] : date('Y-m-d H:i:s'),
                'post_ids' => isset($params['post_ids']) ? $params['post_ids'] : 1,
                'post_title' => isset($params['post_title']) ? $params['post_title'] : 0,
                'kunena_post_link' => isset($params['kunena_post_link']) ? $params['kunena_post_link'] : 0,
                'reminder_lines' => isset($params['reminder_lines']) ? $params['reminder_lines'] : 0,
                'ignored_authors' => isset($params['ignored_authors']) ? $params['ignored_authors'] : ''
            );
            JFactory::getApplication()->enqueueMessage('Form data loaded from getParams: ' . json_encode($data), 'notice');
        } else {
            JFactory::getApplication()->enqueueMessage('Form data loaded from user state', 'notice');
        }

        return $data;
    }

    public function getParams()
    {
        JFactory::getApplication()->enqueueMessage('Fetching params from database', 'notice');
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $query->select('*')
              ->from('#__kunenatopic2article_params')
              ->where('id = 1');
        $db->setQuery($query);
        $row = $db->loadAssoc();

        if (empty($row)) {
            JFactory::getApplication()->enqueueMessage('No params found in database, using defaults', 'warning');
            return array(
                'topic_selection' => 0,
                'article_category' => 0,
                'post_transfer_scheme' => 1,
                'max_article_size' => 40000,
                'post_author' => 1,
                'post_creation_date' => date('Y-m-d'),
                'post_creation_time' => date('Y-m-d H:i:s'),
                'post_ids' => 1,
                'post_title' => 0,
                'kunena_post_link' => 0,
                'reminder_lines' => 0,
                'ignored_authors' => ''
            );
        }

        JFactory::getApplication()->enqueueMessage('Params loaded from database: ' . json_encode($row), 'notice');
        return $row;
    }

    public function save($data)
    {
        JFactory::getApplication()->enqueueMessage('Saving form data: ' . json_encode($data), 'notice');

        // Проверяем, передаются ли данные из формы
        if (empty($data)) {
            JFactory::getApplication()->enqueueMessage('No data to save', 'error');
            return false;
        }

        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $query->update('#__kunenatopic2article_params')
              ->set('topic_selection = ' . $db->quote($data['topic_selection']))
              ->set('article_category = ' . $db->quote($data['article_category']))
              ->set('post_transfer_scheme = ' . $db->quote($data['post_transfer_scheme']))
              ->set('max_article_size = ' . $db->quote($data['max_article_size']))
              ->set('post_author = ' . $db->quote($data['post_author']))
              ->set('post_creation_date = ' . $db->quote($data['post_creation_date']))
              ->set('post_creation_time = ' . $db->quote($data['post_creation_time']))
              ->set('post_ids = ' . $db->quote($data['post_ids']))
              ->set('post_title = ' . $db->quote($data['post_title']))
              ->set('kunena_post_link = ' . $db->quote($data['kunena_post_link']))
              ->set('reminder_lines = ' . $db->quote($data['reminder_lines']))
              ->set('ignored_authors = ' . $db->quote($data['ignored_authors']))
              ->where('id = 1');
        $db->setQuery($query);

        if ($db->execute()) {
            JFactory::getApplication()->enqueueMessage('Data saved successfully', 'success');
            return true;
        } else {
            JFactory::getApplication()->enqueueMessage('Failed to save data', 'error');
            return false;
        }
    }

    public function reset()
    {
        JFactory::getApplication()->enqueueMessage('Resetting parameters', 'notice');

        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $query->update('#__kunenatopic2article_params')
              ->set('topic_selection = 0')
              ->set('article_category = 0')
              ->set('post_transfer_scheme = 1')
              ->set('max_article_size = 40000')
              ->set('post_author = 1')
              ->set('post_creation_date = ' . $db->quote(date('Y-m-d')))
              ->set('post_creation_time = ' . $db->quote(date('Y-m-d H:i:s')))
              ->set('post_ids = 1')
              ->set('post_title = 0')
              ->set('kunena_post_link = 0')
              ->set('reminder_lines = 0')
              ->set('ignored_authors = ' . $db->quote(''))
              ->where('id = 1');
        $db->setQuery($query);

        if ($db->execute()) {
            JFactory::getApplication()->enqueueMessage('Parameters reset successfully', 'success');
            return true;
        } else {
            JFactory::getApplication()->enqueueMessage('Failed to reset parameters', 'error');
            return false;
        }
    }
}
