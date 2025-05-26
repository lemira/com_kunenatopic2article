<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Application\CMSApplication;
use Joomla\Database\DatabaseDriver;

class KunenaTopic2ArticleModelTopic extends AdminModel
{
    protected CMSApplication $app;
    protected DatabaseDriver $db;

    public function __construct($config = [])
    {
        parent::__construct($config);
        $this->app = Factory::getApplication();
        $this->db = Factory::getDbo();
    }

    public function getTable($type = 'Params', $prefix = 'KunenaTopic2ArticleTable', $config = [])
    {
        $table = Table::getInstance($type, $prefix, $config);
        if ($table === false) {
            $this->app->enqueueMessage(Text::sprintf('COM_KUNENATOPIC2ARTICLE_TABLE_LOAD_ERROR', $prefix . $type), 'error');
            return Table::getInstance('Content');
        }

        return $table;
    }

    public function getForm($data = [], $loadData = true)
    {
        $form = $this->loadForm('com_kunenatopic2article.topic', 'topic', ['control' => 'jform', 'load_data' => $loadData]);

        if (empty($form)) {
            $this->app->enqueueMessage(Text::_('COM_KUNENATOPIC2ARTICLE_FORM_LOAD_ERROR'), 'error');
            return false;
        }

        return $form;
    }

    protected function loadFormData()
    {
        $data = $this->app->getUserState('com_kunenatopic2article.edit.topic.data', []);

        if ($this->app->getUserState('com_kunenatopic2article.save.success', false)) {
            $this->app->setUserState('com_kunenatopic2article.edit.topic.data', []);
            $this->app->setUserState('com_kunenatopic2article.save.success', false);
            $data = [];
        }

        if (empty($data)) {
            $params = $this->getParams();
            $data = [
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
            ];
        }

        return $data;
    }

    public function getParams()
    {
        $query = $this->db->getQuery(true)
            ->select('*')
            ->from($this->db->quoteName('#__kunenatopic2article_params'))
            ->where($this->db->quoteName('id') . ' = 1');

        $this->db->setQuery($query);
        $row = $this->db->loadAssoc();

        if (empty($row)) {
            return [
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
            ];
        }

        return $row;
    }

    public function save($data)
    {
        if (empty($data)) {
            $this->app->enqueueMessage(Text::_('COM_KUNENATOPIC2ARTICLE_NO_DATA_TO_SAVE'), 'error');
            return false;
        }

        $query = $this->db->getQuery(true)
            ->update($this->db->quoteName('#__kunenatopic2article_params'))
            ->set([
                $this->db->quoteName('topic_selection') . ' = ' . $this->db->quote($data['topic_selection']),
                $this->db->quoteName('article_category') . ' = ' . $this->db->quote($data['article_category']),
                $this->db->quoteName('post_transfer_scheme') . ' = ' . $this->db->quote($data['post_transfer_scheme']),
                $this->db->quoteName('max_article_size') . ' = ' . $this->db->quote($data['max_article_size']),
                $this->db->quoteName('post_author') . ' = ' . $this->db->quote($data['post_author']),
                $this->db->quoteName('post_creation_date') . ' = ' . $this->db->quote($data['post_creation_date']),
                $this->db->quoteName('post_creation_time') . ' = ' . $this->db->quote($data['post_creation_time']),
                $this->db->quoteName('post_ids') . ' = ' . $this->db->quote($data['post_ids']),
                $this->db->quoteName('post_title') . ' = ' . $this->db->quote($data['post_title']),
                $this->db->quoteName('kunena_post_link') . ' = ' . $this->db->quote($data['kunena_post_link']),
                $this->db->quoteName('reminder_lines') . ' = ' . $this->db->quote($data['reminder_lines']),
                $this->db->quoteName('ignored_authors') . ' = ' . $this->db->quote($data['ignored_authors'])
            ])
            ->where($this->db->quoteName('id') . ' = 1');

        $this->db->setQuery($query);

        try {
            $this->db->execute();
            $this->app->setUserState('com_kunenatopic2article.save.success', true);
            return true;
        } catch (Exception $e) {
            $this->app->enqueueMessage(Text::_('COM_KUNENATOPIC2ARTICLE_SAVE_FAILED') . ': ' . $e->getMessage(), 'error');
            return false;
        }
    }

    public function reset()
    {
        try {
            $query = $this->db->getQuery(true)
                ->update($this->db->quoteName('#__kunenatopic2article_params'))
                ->set([
                    $this->db->quoteName('topic_selection') . ' = 0',
                    $this->db->quoteName('article_category') . ' = 0',
                    $this->db->quoteName('post_transfer_scheme') . ' = 1',
                    $this->db->quoteName('max_article_size') . ' = 40000',
                    $this->db->quoteName('post_author') . ' = 1',
                    $this->db->quoteName('post_creation_date') . ' = 0',
                    $this->db->quoteName('post_creation_time') . ' = 0',
                    $this->db->quoteName('post_ids') . ' = 0',
                    $this->db->quoteName('post_title') . ' = 0',
                    $this->db->quoteName('kunena_post_link') . ' = 0',
                    $this->db->quoteName('reminder_lines') . ' = 0',
                    $this->db->quoteName('ignored_authors') . ' = ' . $this->db->quote('')
                ])
                ->where($this->db->quoteName('id') . ' = 1');

            $this->db->setQuery($query);
            $this->db->execute();

            return true;
        } catch (Exception $e) {
            $this->app->enqueueMessage(Text::_('COM_KUNENATOPIC2ARTICLE_RESET_FAILED') . ': ' . $e->getMessage(), 'error');
            return false;
        }
    }
}
