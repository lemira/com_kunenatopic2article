<?php
defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;

class KunenaTopic2ArticleModelTopic extends ListModel
{
    protected $text_prefix = 'COM_KUNENATOPIC2ARTICLE';

    public function __construct($config = [])
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = ['id', 'topic_selection', 'article_category'];
        }
        parent::__construct($config);
    }

    protected function getListQuery()
    {
        $db = $this->getDbo();
        $query = $db->getQuery(true);
        $query->select('*')
              ->from($db->quoteName('#__kunenatopic2article_params'));
        return $query;
    }
}
