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

<?php

<?php

/**
 * Проверка существования темы и получение ее данных
 */
protected function getTopicData($topicId)
{
    $this->subject = ''; // Инициализируем subject
    
    // ОТЛАДКА: проверяем входящий параметр
    error_log("DEBUG getTopicData: входящий topicId = " . $topicId);
    
    try {
        $query = $this->db->getQuery(true)
            ->select(['subject', 'first_post_id', 'hold']) // Добавляем поля для отладки
            ->from($this->db->quoteName('#__kunena_topics'))
            ->where($this->db->quoteName('first_post_id') . ' = ' . $this->db->quote((int)$topicId))
            ->where($this->db->quoteName('hold') . ' = 0');

        // ОТЛАДКА: выводим SQL запрос
        $sqlQuery = (string)$query;
        error_log("DEBUG getTopicData: SQL запрос = " . $sqlQuery);

        $result = $this->db->setQuery($query)->loadObject();

        // ОТЛАДКА: проверяем результат запроса
        if ($result) {
            error_log("DEBUG getTopicData: найдена тема с subject = '" . $result->subject . "', first_post_id = " . $result->first_post_id . ", hold = " . $result->hold);
            $this->subject = $result->subject;
        } else {
            error_log("DEBUG getTopicData: тема не найдена");
            
            // Дополнительная проверка: есть ли вообще такая запись в таблице?
            $checkQuery = $this->db->getQuery(true)
                ->select(['subject', 'first_post_id', 'hold'])
                ->from($this->db->quoteName('#__kunena_topics'))
                ->where($this->db->quoteName('first_post_id') . ' = ' . $this->db->quote((int)$topicId));
            
            $checkResult = $this->db->setQuery($checkQuery)->loadObject();
            if ($checkResult) {
                error_log("DEBUG getTopicData: найдена запись с first_post_id = " . $topicId . ", но hold = " . $checkResult->hold . " (должно быть 0)");
            } else {
                error_log("DEBUG getTopicData: записи с first_post_id = " . $topicId . " вообще не существует");
            }
        }

        // ОТЛАДКА: проверяем итоговое значение subject
        error_log("DEBUG getTopicData: итоговый subject = '" . $this->subject . "'");

    } catch (\Exception $e) {
        error_log("DEBUG getTopicData: ИСКЛЮЧЕНИЕ - " . $e->getMessage());
        $this->app->enqueueMessage($e->getMessage(), 'error');
    }
}

public function save($data)
{
    // ОТЛАДКА: проверяем входящие данные
    error_log("DEBUG save: входящие данные = " . json_encode($data));
    
    // Получаем originalTopicId из формы
    $originalTopicId = !empty($data['topic_selection']) && is_numeric($data['topic_selection']) ? (int)$data['topic_selection'] : 0;
    
    // ОТЛАДКА: проверяем originalTopicId
    error_log("DEBUG save: originalTopicId = " . $originalTopicId);
    
    if ($originalTopicId <= 0) {
        error_log("DEBUG save: originalTopicId <= 0, возвращаем false");
        $this->app->enqueueMessage('Topic ID должно быть числом больше 0', 'error');
        return false;
    }

    // Вызываем getTopicData для проверки темы
    error_log("DEBUG save: вызываем getTopicData с ID = " . $originalTopicId);
    $this->getTopicData($originalTopicId);

    // ОТЛАДКА: проверяем subject после getTopicData
    error_log("DEBUG save: после getTopicData subject = '" . $this->subject . "'");

    // Проверяем, найдена ли тема (subject не пустой)
    if ($this->subject !== '') {
        error_log("DEBUG save: тема найдена, сохраняем данные");
        
        // Тема найдена - сохраняем originalTopicId обратно в данные
        $data['topic_selection'] = $originalTopicId;

        // Отправляем форму в таблицу
        $table = new ParamsTable($this->db);

        if (!$table->load(1)) {
            error_log("DEBUG save: ошибка загрузки таблицы");
            $this->app->enqueueMessage(Text::_('COM_KUNENATOPIC2ARTICLE_SAVE_FAILED') . ': ' . Text::_('JLIB_DATABASE_ERROR_LOAD_FAILED'), 'error');
            return false;
        }

        $table->bind($data);

        if (!$table->store()) {
            error_log("DEBUG save: ошибка сохранения в таблицу - " . $table->getError());
            $this->app->enqueueMessage(Text::_('COM_KUNENATOPIC2ARTICLE_SAVE_FAILED') . ': ' . $table->getError(), 'error');
            return false;
        }

        error_log("DEBUG save: данные успешно сохранены");
        // Устанавливаем успешное состояние для активации кнопки Create
        $this->app->setUserState('com_kunenatopic2article.save.success', true);
        return true;
        
    } else {
        error_log("DEBUG save: тема не найдена, выводим ошибку");
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
