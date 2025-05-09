<?php
defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Factory;

class KunenaTopic2ArticleModelTopic extends BaseDatabaseModel
{
    public function getParameters()
    {
        $db = $this->getDbo();
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__kunena_article'))
            ->where($db->quoteName('id') . ' = 1');
        $db->setQuery($query);
        return $db->loadObject() ?: new \stdClass();
    }

    public function saveParameters($data)
    {
        $db = $this->getDbo();
        $params = new \stdClass();
        $params->id = 1;
        $params->topic_selection = $data['topic_selection'] ?? 0;
        $params->article_category = $data['article_category'] ?? '';
        $params->post_transfer_scheme = $data['post_transfer_scheme'] ?? 'sequential';
        $params->max_article_size = $data['max_article_size'] ?? 40000;
        $params->post_author = $data['post_author'] ?? 0;
        $params->post_creation_date = $data['post_creation_date'] ?? 0;
        $params->post_creation_time = $data['post_creation_time'] ?? 0;
        $params->post_ids = $data['post_ids'] ?? 0;
        $params->post_title = $data['post_title'] ?? 0;
        $params->kunena_post_link = $data['kunena_post_link'] ?? 0;
        $params->reminder_lines = $data['reminder_lines'] ?? 0;
        $params->ignored_authors = $data['ignored_authors'] ?? '';

        try {
            $db->updateObject('#__kunena_article', $params, 'id') || $db->insertObject('#__kunena_article', $params);
            return true;
        } catch (Exception $e) {
            Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
            return false;
        }
    }

    public function resetParameters()
    {
        $db = $this->getDbo();
        $query = $db->getQuery(true)
            ->delete($db->quoteName('#__kunena_article'))
            ->where($db->quoteName('id') . ' = 1');
        $db->setQuery($query);
        return $db->execute();
    }
}
