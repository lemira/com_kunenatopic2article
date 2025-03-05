<?php
defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\AdminModel;

class KunenaTopic2ArticleModelTopic extends AdminModel
{
    protected $text_prefix = 'COM_KUNENATOPIC2ARTICLE';

    public function getTable($type = 'Topic', $prefix = 'KunenaTopic2ArticleTable', $config = array())
    {
        return JTable::getInstance($type, $prefix, $config);
    }

    protected function prepareTable($table)
    {
        $table->title = htmlspecialchars_decode($table->title, ENT_QUOTES);
    }
}
