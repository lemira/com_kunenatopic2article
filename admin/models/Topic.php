<?php 
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Application\CMSApplication;
use Joomla\Database\DatabaseDriver;
use Joomla\Component\KunenaTopic2Article\Administrator\Table\ParamsTable;
use Joomla\Database\DatabaseInterface;

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

    public function getForm($data = [], $loadData = true): ?Form
    {
        $form = $this->loadForm(
            'com_kunenatopic2article.topic',
            'topic',
            ['control' => 'jform', 'load_data' => $loadData]
        );

        if (!$form) {
            $this->app->enqueueMessage(Text::_('COM_KUNENATOPIC2ARTICLE_FORM_LOAD_ERROR'), 'error');
            return null;
        }

        return $form;
    }

    protected function loadFormData(): array
    {
        $data = $this->app->getUserState('com_kunenatopic2article.edit.topic.data', []);

        if ($this->app->getUserState('com_kunenatopic2article.save.success', false)) {
            $this->app->setUserState('com_kunenatopic2article.edit.topic.data', []);
            $this->app->setUserState('com_kunenatopic2article.save.success', false);
            $data = [];
        }

        if (empty($data)) {
            $params = $this->getParams();
            $data = $params ? $params->getProperties() : [];
        }

        return $data;
    }

    public function getParams(): ?ParamsTable
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $table = new ParamsTable($db);

        if (!$table->load(1)) {
            return null;
        }

        return $table;
    }

    public function save($data)
    {
        if (empty($data)) {
            $this->app->enqueueMessage(Text::_('COM_KUNENATOPIC2ARTICLE_NO_DATA_TO_SAVE'), 'error');
            return false;
        }

        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $table = new ParamsTable($db);

        if (!$table->load(1)) {
            $this->app->enqueueMessage(Text::_('COM_KUNENATOPIC2ARTICLE_SAVE_FAILED') . ': ' . Text::_('JLIB_DATABASE_ERROR_LOAD_FAILED'), 'error');
            return false;
        }

        $table->bind($data);

        if (!$table->check() || !$table->store()) {
            $this->app->enqueueMessage(Text::_('COM_KUNENATOPIC2ARTICLE_SAVE_FAILED') . ': ' . $table->getError(), 'error');
            return false;
        }

        $this->app->setUserState('com_kunenatopic2article.save.success', true);
        return true;
    }

    public function reset()
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $table = new ParamsTable($db);

        if (!$table->load(1)) {
            $this->app->enqueueMessage(Text::_('COM_KUNENATOPIC2ARTICLE_RESET_FAILED') . ': ' . Text::_('JLIB_DATABASE_ERROR_LOAD_FAILED'), 'error');
            return false;
        }

        $defaults = [
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

        $table->bind($defaults);

        if (!$table->check() || !$table->store()) {
            $this->app->enqueueMessage(Text::_('COM_KUNENATOPIC2ARTICLE_RESET_FAILED') . ': ' . $table->getError(), 'error');
            return false;
        }

        return true;
    }
}
