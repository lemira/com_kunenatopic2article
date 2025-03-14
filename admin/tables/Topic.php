<?php
defined('_JEXEC') or die;

// Отладка: проверяем загрузку файла
if (!defined('JPATH_COMPONENT_ADMINISTRATOR')) {
    JFactory::getApplication()->enqueueMessage('JPATH_COMPONENT_ADMINISTRATOR not defined', 'error');
}

class KunenaTopic2ArticleTableTopic extends JTable
{
    public $id = null;
    public $topic_selection = null;
    public $article_category = null;
    public $post_transfer_scheme = null;
    public $max_article_size = null;
    public $post_author = null;
    public $post_creation_date = null;
    public $post_creation_time = null;
    public $post_ids = null;
    public $post_title = null;
    public $kunena_post_link = null;
    public $reminder_lines = null;
    public $ignored_authors = null;

    public function __construct(&$db)
    {
        JFactory::getApplication()->enqueueMessage('Constructing KunenaTopic2ArticleTableTopic', 'notice'); // Отладка
        parent::__construct('#__kunenatopic2article_params', 'id', $db);
    }

    public function check()
    {
        return true; // Минимальная реализация check
    }
}
