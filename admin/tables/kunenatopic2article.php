<?php
defined('_JEXEC') or die('Restricted access');

class KunenaTopic2ArticleTableKunenaTopic2Article extends JTable
{
    public function __construct(&$db)
    {
        parent::__construct('#__kunena_topic2article_params', 'id', $db);
    }
}
