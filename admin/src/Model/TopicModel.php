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
        $this->db = Factory::getDbo(); // Используем getDbo() для получения базы данных
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
                    $data['topic_selection'] = $topic->subject;
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
                ->select(['id', 'subject'])
                ->from($this->db->quoteName('#__kunena_topics'))
                ->where($this->db->quoteName('id') . ' = ' . (int)$topicId);

            $topic = $this->db->setQuery($query)->loadObject();

            if (!$topic) {
                throw new \Exception(Text::sprintf('COM_KUNENATOPIC2ARTICLE_ERROR_INVALID_TOPIC_ID', $topicId));
            }

            return $topic;
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

        // Проверка Topic ID
        $topicId = !empty($data['topic_selection']) && is_numeric($data['topic_selection']) ? (int)$data['topic_selection'] : 0;
        if ($topicId === 0 || !$this->getTopicData($topicId)) {
            $this->app->setUserState('com_kunenatopic2article.topic_id', 0); // Сбрасываем topic_id при ошибке
            $this->app->enqueueMessage(Text::sprintf('COM_KUNENATOPIC2ARTICLE_ERROR_INVALID_TOPIC_ID', $topicId ?: 'пустое'), 'error');
            return false;
        }

        // Сохраняем topic_id для отображения заголовка
        $this->app->setUserState
