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
    protected string $subject = ''; // Переменная модели для хранения subject

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
                $this->getTopicData($topicId); // Заполняем $subject
                if ($this->subject) {
                    $data['topic_selection'] = $this->subject; // Показываем subject в форме
                } else {
                    $data['topic_selection'] = ''; // Если не найдено, ставим пустую строку
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
    $this->subject = ''; // Инициализируем subject
    
    // ОТЛАДКА: проверяем входящий параметр
    $this->app->enqueueMessage("DEBUG getTopicData: входящий topicId = " . $topicId, 'notice');
    
    try {
        $query = $this->db->getQuery(true)
            ->select(['subject', 'first_post_id', 'hold']) // Добавляем поля для отладки
            ->from($this->db->quoteName('#__kunena_topics'))
            ->where($this->db->quoteName('first_post_id') . ' = ' . $this->db->quote((int)$topicId))
            ->where($this->db->quoteName('hold') . ' = 0');

        // ОТЛАДКА: выводим SQL запрос
        $sqlQuery = (string)$query;
        $this->app->enqueueMessage("DEBUG getTopicData: SQL = " . $sqlQuery, 'notice');

        $result = $this->db->setQuery($query)->loadObject();

        // ОТЛАДКА: проверяем результат запроса
        if ($result) {
            $this->app->enqueueMessage("DEBUG getTopicData: найдена тема с subject = '" . $result->subject . "'", 'notice');
            $this->subject = $result->subject;
        } else {
            $this->app->enqueueMessage("DEBUG getTopicData: тема не найдена", 'warning');
        }

        // ОТЛАДКА: проверяем итоговое значение subject
        $this->app->enqueueMessage("DEBUG getTopicData: итоговый subject = '" . $this->subject . "'", 'notice');

    } catch (\Exception $e) {
        $this->app->enqueueMessage("DEBUG getTopicData: ИСКЛЮЧЕНИЕ - " . $e->getMessage(), 'error');
    }
}

public function save($data)
{
    // ОТЛАДКА: проверяем входящие данные
    $this->app->enqueueMessage("DEBUG save: topic_selection = " . ($data['topic_selection'] ?? 'НЕ УСТАНОВЛЕНО'), 'notice');
    
    // Получаем originalTopicId из формы
    $originalTopicId = !empty($data['topic_selection']) && is_numeric($data['topic_selection']) ? (int)$data['topic_selection'] : 0;
    
    // ОТЛАДКА: проверяем originalTopicId
    $this->app->enqueueMessage("DEBUG save: originalTopicId = " . $originalTopicId, 'notice');
    
    if ($originalTopicId <= 0) {
        $this->app->enqueueMessage("DEBUG save: originalTopicId <= 0", 'warning');
        $this->app->enqueueMessage('Topic ID должно быть числом больше 0', 'error');
        return false;
    }

    // Вызываем getTopicData для проверки темы
    $this->app->enqueueMessage("DEBUG save: вызываем getTopicData с ID = " . $originalTopicId, 'notice');
    $this->getTopicData($originalTopicId);

    // ОТЛАДКА: проверяем subject после getTopicData
    $this->app->enqueueMessage("DEBUG save: после getTopicData subject = '" . $this->subject . "'", 'notice');

    // Проверяем, найдена ли тема (subject не пустой)
    if ($this->subject !== '') {
        $this->app->enqueueMessage("DEBUG save: тема найдена, сохраняем данные", 'notice');
        
        // Тема найдена - сохраняем originalTopicId обратно в данные
        $data['topic_selection'] = $originalTopicId;

        // Отправляем форму в таблицу
        $table = new ParamsTable($this->db);

        if (!$table->load(1)) {
            $this->app->enqueueMessage(Text::_('COM_KUNENATOPIC2ARTICLE_SAVE_FAILED') . ': ' . Text::_('JLIB_DATABASE_ERROR_LOAD_FAILED'), 'error');
            return false;
        }

        $table->bind($data);

        if (!$table->store()) {
            $this->app->enqueueMessage(Text::_('COM_KUNENATOPIC2ARTICLE_SAVE_FAILED') . ': ' . $table->getError(), 'error');
            return false;
        }

        $this->app->enqueueMessage("DEBUG save: данные успешно сохранены", 'success');
        // Устанавливаем успешное состояние для активации кнопки Create
        $this->app->setUserState('com_kunenatopic2article.save.success', true);
        return true;
        
    } else {
        $this->app->enqueueMessage("DEBUG save: тема не найдена, subject пустой", 'warning');
        // Тема не найдена - выводим ошибку с originalTopicId
        $this->app->enqueueMessage(Text::sprintf('COM_KUNENATOPIC2ARTICLE_ERROR_INVALID_TOPIC_ID', $originalTopicId), 'error');
        $data['topic_selection'] = ''; // Сбрасываем Topic ID в форме
        $this->app->setUserState('com_kunenatopic2article.topic_id', 0); // Сбрасываем topic_id
        return false;
    }
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
