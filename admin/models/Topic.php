<?php
defined('_JEXEC') or die;

class KunenaTopic2ArticleModelTopic extends JModelAdmin
{
    public function getTable($type = 'Topic', $prefix = 'KunenaTopic2ArticleTable', $config = array())
    {
        $db = JFactory::getDbo();
        $tableName = '#__kunenatopic2article_params';
        $query = $db->getQuery(true)
                    ->select('COUNT(*)')
                    ->from($db->quoteName($tableName));
        $db->setQuery($query);
        $exists = $db->loadResult();

        if (!$exists) {
            JFactory::getApplication()->enqueueMessage('Table ' . $tableName . ' not found. Please reinstall the component.', 'error');
            return JTable::getInstance('Content'); // Возвращаем заглушку, чтобы избежать ошибки
        }

        return JTable::getInstance($type, $prefix, $config);
    }

    public function getForm($data = array(), $loadData = true)
    {
        $form = $this->loadForm('com_kunenatopic2article.topic', 'topic', array('control' => 'jform', 'load_data' => $loadData));
        if (empty($form)) {
            return false;
        }
        return $form;
    }

    protected function loadFormData()
    {
        $data = JFactory::getApplication()->getUserState('com_kunenatopic2article.edit.topic.data', array());
        if (empty($data)) {
            $data = $this->getParams();
        }
        return $data;
    }

    public function getParams()
    {
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $query->select('*')
              ->from('#__kunenatopic2article_params')
              ->where('id = 1');
        $db->setQuery($query);
        $row = $db->loadAssoc();

        if (empty($row)) {
            return [
                'topic_selection' => 0,
                'article_category' => 0,
                'post_transfer_scheme' => 1,
                'max_article_size' => 40000,
                'post_author' => 1,
                'post_creation_date' => date('Y-m-d H:i:s'),
                'post_creation_time' => date('Y-m-d H:i:s'),
                'post_ids' => 1,
                'post_title' => 0,
                'kunena_post_link' => 0,
                'reminder_lines' => 0,
                'ignored_authors' => ''
            ];
        }

        return $row;
    }

    public function save($data)
    {
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
        return $db->execute();
    }

    public function reset()
    {
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $query->update('#__kunenatopic2article_params')
              ->set('topic_selection = 0')
              ->set('article_category = 0')
              ->set('post_transfer_scheme = 1')
              ->set('max_article_size = 40000')
              ->set('post_author = 1')
              ->set('post_creation_date = ' . $db->quote(date('Y-m-d H:i:s')))
              ->set('post_creation_time = ' . $db->quote(date('Y-m-d H:i:s')))
              ->set('post_ids = 1')
              ->set('post_title = 0')
              ->set('kunena_post_link = 0')
              ->set('reminder_lines = 0')
              ->set('ignored_authors = NULL')
              ->where('id = 1');
        $db->setQuery($query);
        return $db->execute();
    }
}
