<?php
defined('_JEXEC') or die;

class KunenaTopic2ArticleTableParams extends JTable
{
    public function __construct($db)
    {
        parent::__construct('#__kunenatopic2article_params', 'id', $db);
    }
}
