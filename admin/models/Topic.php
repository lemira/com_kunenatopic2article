<?php
defined('_JEXEC') or die;

class KunenaTopic2ArticleModelTopic extends JModelAdmin
{
    public function getTable($type = 'Params', $prefix = 'KunenaTopic2ArticleTable', $config = array())
    {
        $table = JTable::getInstance($type, $prefix, $config);
        if ($table === false) {
            JFactory::getApplication()->enqueueMessage('Failed to load table ' . $prefix . $type, 'error');
            return JTable::getInstance('Content');
        }

        return $table;
    }

    public function getForm($data = array(), $loadData = true)
    {
        $form = $this->loadForm('com_kunenatopic2article.topic', 'topic', array('control' => 'jform', 'load_data' => $loadData));
        if (empty($form)) {
            JFactory::getApplication()->enqueueMessage('Failed to load form com_kunenatopic2article.topic', 'error');
            return false;
        }

        return $form;
    }

    protected function loadFormData()
    {
        $app = JFactory::getApplication();
        $data = $app->getUserState('com_kunenatopic2article.edit.topic.data', array());

        // После успешного сохранения или сброса загружаем данные из базы
        if ($app->getUserState('com_kunenatopic2article.save.success', false)) {
            $app->setUserState('com_kunenatopic2article.edit.topic.data', array());
            $app->setUserState('com_kunenatopic2article.save.success', false);
            $data = array();
        }

        if (empty($data)) {
            $params = $this->getParams();
            $data = array(
                'topic_selection' => (string)$params['topic_selection'],
                'article_category' => (string)$params['article_category'],
                'post_transfer_scheme' => (string)$params['post_transfer_scheme'],
                'max_article_size' => (string)$params['max_article_size'],
                'post_author' => (string)$params['post_author'],
                'post_creation_date' => (string)$params['post_creation_date'],
                'post_creation_time' => (string)$params['post_creation_time'],
                'post_ids' => (string)$params['post_ids'],
                'post_title' => (string)$params['post_title'],
                'kunena_post_link' => (string)$params['kunena_post_link'],
                'reminder_lines' => (string)$params['reminder_lines'],
                'ignored_authors' => (string)$params['ignored_authors']
            );
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
            return array(
                'topic_selection' => '0',
                'article_category' => '0',
                'post_transfer_scheme' => '1',
                'max_article_size' => '40000',
                'post_author' => '1',
                'post_creation_date' => '0',
                'post_creation_time' => '0',
                'post_ids' => '0',
                'post_title' => '0',
                'kunena_post_link' => '0',
                'reminder_lines' => '0',
                'ignored_authors' => ''
            );
        }

        return $row;
    }

    public function save($data)
    {
        if (empty($data)) {
            JFactory::getApplication()->enqueueMessage('No form data to save', 'error');
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

        try {
            $db->execute();
            $app = JFactory::getApplication();
            $app->setUserState('com_kunenatopic2article.save.success', true);
            return true;
        } catch (Exception $e) {
            JFactory::getApplication()->enqueueMessage('Save failed with error: ' . $e->getMessage(), 'error');
            return false;
        }
    }
public function reset()
{
    $app = JFactory::getApplication();
    try {
        // Подключение к базе данных
        $db = JFactory::getDbo();
        
        // Значения по умолчанию из install.mysql.utf8.sql
        $query = $db->getQuery(true)
            ->update($db->quoteName('#__kunenatopic2article_params'))
            ->set([
                $db->quoteName('topic_selection') . ' = 0',
                $db->quoteName('article_category') . ' = 0',
                $db->quoteName('post_transfer_scheme') . ' = 1',
                $db->quoteName('max_article_size') . ' = 40000',
                $db->quoteName('post_author') . ' = 1',
                $db->quoteName('post_creation_date') . ' = 0',
                $db->quoteName('post_creation_time') . ' = 0',
                $db->quoteName('post_ids') . ' = 0',
                $db->quoteName('post_title') . ' = 0',
                $db->quoteName('kunena_post_link') . ' = 0',
                $db->quoteName('reminder_lines') . ' = 0',
                $db->quoteName('ignored_authors') . ' = ' . $db->quote('')
            ])
            ->where($db->quoteName('id') . ' = 1');
        
        $db->setQuery($query);
        $result = $db->execute();
        
        if (!$result) {
            throw new Exception($db->getErrorMsg());
        }
        
        return true;
    } catch (Exception $e) {
        $app->enqueueMessage('Error in reset method: ' . $e->getMessage(), 'error');
        return false;
    }
}
    
 }
