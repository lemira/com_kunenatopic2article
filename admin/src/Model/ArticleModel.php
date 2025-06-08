<?php
namespace Joomla\Component\KunenaTopic2Article\Administrator\Model;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\Component\KunenaTopic2Article\Administrator\Table\ParamsTable;
use Joomla\Database\DatabaseInterface;
use Joomla\CMS\Table\Table;

class TopicModel extends AdminModel
{
    protected CMSApplication $app;
    protected DatabaseInterface $db;

    public function __construct($config = [])
    {
        parent::__construct($config);
        $this->app = Factory::getApplication();
        $this->db = Factory::getDbo();
    }

    /**
     * Метод для получения таблицы
     */
    public function getTable($name = '', $prefix = '', $options = []): Table
    {
        return new ParamsTable($this->db);
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

        if (empty($data)) {
            $params = $this->getParams();
            $data = $params ? $params->getProperties() : [];
            $topicId = $this->app->getUserState('com_kunenatopic2article.topic_id', 0);
            if ($topicId) {
                $topic = $this->getTopicData($topicId);
                if ($topic) {
                    $data['topic_selection'] = $topic->subject; // Показываем subject в форме
                } else {
                    $data['topic_selection'] = 0; // Если не найдено, ставим 0
                }
            }
        }

        return $data;
    }

    public function getParams(): ?ParamsTable
    {
        $table = new ParamsTable($this->db);

        if (!$table->load(1)) {
            return null;
        }

        return $table;
    }

    /**
     * Проверка существования темы и получение ее данных
     */
    protected function getTopicData($topicId)
    {
        try {
            $query = $this->db->getQuery(true)
                ->select(['subject'])
                ->from($this->db->quoteName('#__kunena_topics'))
                ->where($this->db->quoteName('first_post_id') . ' = ' . $this->db->quote((int)$topicId))
                ->where($this->db->quoteName('hold') . ' = 0');

            $topic = $this->db->setQuery($query)->loadObject();

            return $topic; // Возвращаем объект с subject или null, если не найдено
        } catch (\Exception $e) {
            $this->app->enqueueMessage($e->getMessage(), 'error');
            return null;
        }
    }

    public function save($data)
    {
        if (empty($data)) {
            $this->app->enqueueMessage(Text::_('COM_KUNENATOPIC2ARTICLE_NO_DATA_TO_SAVE'), 'error');
            return false;
        }

        // Сохраняем исходный topicId как временную переменную
        $originalTopicId = !empty($data['topic_selection']) && is_numeric($data['topic_selection']) ? (int)$data['topic_selection'] : 0;

        // Проверка Topic ID (только по first_post_id)
        $topic = $this->getTopicData($originalTopicId);
        if ($originalTopicId === 0 || !$topic) {
            $this->app->setUserState('com_kunenatopic2article.topic_id', 0);
            $this->app->enqueueMessage(Text::sprintf('COM_KUNENATOPIC2ARTICLE_ERROR_INVALID_TOPIC_ID', $originalTopicId), 'error');
            return false;
        }

        // Восстанавливаем originalTopicId в topic_selection перед сохранением
        $data['topic_selection'] = $originalTopicId;

        // Сохраняем first_post_id как topic_id
        $this->app->setUserState('com_kunenatopic2article.topic_id', $originalTopicId);

        // Сохраняем все данные, включая topic_selection как first_post_id
        $table = new ParamsTable($this->db);

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
        $table = new ParamsTable($this->db);

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

        $this->app->setUserState('com_kunenatopic2article.save.success', false);
        $this->app->setUserState('com_kunenatopic2article.topic_id', 0);
        return true;
    }

    public function create()
    {
        $this->app->setUserState('com_kunenatopic2article.save.success', false);
        $this->app->setUserState('com_kunenatopic2article.topic_id', 0);
        return true;
    }
}
