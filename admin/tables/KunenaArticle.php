<?php
defined('_JEXEC') or die;

use Joomla\CMS\Table\Table;

class TableKunenaArticle extends Table
{
    public $id = null;
    public $topic_selection = 0;
    public $article_category = '';
    public $post_transfer_scheme = 'sequential';
    public $max_article_size = 40000;
    public $post_author = 0;
    public $post_creation_date = 0;
    public $post_creation_time = 0;
    public $post_ids = 0;
    public $post_title = 0;
    public $kunena_post_link = 0;
    public $reminder_lines = 0;
    public $ignored_authors = '';

    public function __construct($db)
    {
        parent::__construct('#__kunena_article', 'id', $db);
    }
}
