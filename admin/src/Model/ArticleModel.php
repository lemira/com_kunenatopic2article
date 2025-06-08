<?php
namespace Joomla\Component\KunenaTopic2Article\Administrator\Model;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
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
        // Настраиваем логирование
        Log::addLogger(
            ['logger' => 'formattedtext'],
            Log::ALL,
            ['com_kunenatopic2article']
        );
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
            Log::add('Ошибка загрузки формы: ' . Text::_('COM_KUNENATOPIC2ARTICLE_FORM_LOAD_ERROR'), Log::ERROR, 'com_kunenatopic2article');
            $this->app->enqueueMessage(Text::_('COM_KUNENATOPIC2ARTICLE_FORM_LOAD_ERROR'), 'error');
            return null;
        }

        Log::add('Форма успешно загружена', Log::INFO, 'com_kunenatopic2article');
        return $form;
    }

    protected function loadFormData(): array
    {
        $data = $this->app->getUserState('com_kunenatopic2article.edit.topic.data', []);

        Log::add('Начало загрузки данных формы. Текущие данные: ' . print_r($data, true), Log::DEBUG, 'com_kunenatopic2article');

        if (empty($data)) {
            $params = $this->getParams();
            $data = $params ? $params->getProperties() : [];
            Log::add('Данные из параметров: ' . print_r($data, true), Log::DEBUG, 'com_kunenatopic2article');
            $topicId = $this->app->getUserState('com_kunenatopic2article.topic_id', 0);
            Log::add('Topic ID из сессии: ' . $topicId, Log::DEBUG, 'com_kunenatopic2article');
            if ($topicId) {
                $this->getTopicData($topicId); // Заполняем $subject
                Log::add('После getTopicData, subject: ' . $this->subject, Log::DEBUG, 'com_kunenatopic2article');
                if ($this->subject) {
                    $data['topic_selection'] = $this->subject; // Показываем subject в форме
                    Log::add('Установлен topic_selection: ' . $this->subject, Log::INFO, 'com_kunenatopic2article');
                } else {
                    $data['topic_selection'] = ''; // Если не найдено, ставим пустую строку
                    Log::add('Тема не найдена, topic_selection сброшен на пустую строку', Log::WARNING, 'com_kunenatopic2article');
                }
            }
        }

        Log::add('Данные формы после обработки: ' . print_r($data, true), Log::DEBUG, 'com_kunenatopic2article');
        return $data;
    }

    public function getParams(): ?ParamsTable
    {
        $table = new ParamsTable($this->db);

        if (!$table->load(1)) {
            Log::add('Ошибка загрузки параметров: ' . Text::_('JLIB_DATABASE_ERROR_LOAD_FAILED'), Log::ERROR, 'com_kunenatopic2article');
            return null;
        }

        Log::add('Параметры успешно загружены', Log::INFO, 'com_kunenatopic2article');
        return $table;
    }

    /**
     * Проверка существования темы и получение ее данных
     */
    protected function getTopicData($topicId)
    {
        $this->subject = ''; // Инициализируем subject
        // Сохраняем введённое Topic ID
        $originalTopicId = !empty($topicId) && is_numeric($topicId) ? (int)$topicId : 0;
        Log::add('Начало getTopicData. originalTopicId: ' . $originalTopicId, Log::DEBUG, 'com_kunenatopic2article');

        try {
            $query = $this->db->getQuery(true)
                ->select(['subject'])
                ->from($this->db->quoteName('#__kunena_topics'))
                ->where($this->db->quoteName('first_post_id') . ' = ' . $this->db->quote((int)$originalTopicId))
                ->where($this->db->quoteName('hold') . ' = 0');

            Log::add('SQL-запрос: ' . $query->dump(), Log::DEBUG, 'com_kunenatopic2article');
            $result = $this->db->setQuery($query)->loadObject();

            if ($result) {
                $this->subject = $result->subject; // Присваиваем subject
                Log::add('Тема найдена. subject: ' . $this->subject, Log::INFO, 'com_kunenatopic2article');
                $this->app->setUserState('com_kunenatopic2article.edit.topic.data.topic_selection', $this->subject); // Обновляем форму через сессию
                Log::add('Обновлено topic_selection в сессии: ' . $this->subject, Log::INFO, 'com_kunenatopic2article');
            } else {
                Log::add('Тема не найдена для first_post_id: ' . $originalTopicId, Log::WARNING, 'com_kunenatopic2article');
            }
        } catch (\Exception $e) {
            Log::add('Ошибка в getTopicData: ' . $e->getMessage(), Log::ERROR, 'com_kunenatopic2article');
        }

        Log::add('Конец getTopicData. subject: ' . $this->subject, Log::DEBUG, 'com_kunenatopic2article');
    }

    public function save($data)
    {
        Log::add('Начало save. Входные данные: ' . print_r($data, true), Log::DEBUG, 'com_kunenatopic2article');

        if (empty($data)) {
            Log::add('Данные пусты, сохранение прервано', Log::ERROR, 'com_kunenatopic2article');
            $this->app->enqueueMessage(Text::_('COM_KUNENATOPIC2ARTICLE_NO_DATA_TO_SAVE'), 'error');
            return false;
        }

        // Вызываем getTopicData
        $this->getTopicData(!empty($data['topic_selection']) && is_numeric($data['topic_selection']) ? (int)$data['topic_selection'] : 0);
        Log::add('После getTopicData, subject: ' . $this->subject, Log::DEBUG, 'com_kunenatopic2article');

        // Проверяем $subject
        if ($this->subject !== '') {
            // Возвращаем originalTopicId в Topic ID перед сохранением
            $originalTopicId = !empty($data['topic_selection']) && is_numeric($data['topic_selection']) ? (int)$data['topic_selection'] : 0;
            $data['topic_selection'] = $originalTopicId;
            Log::add('Успешная проверка, originalTopicId: ' . $originalTopicId . ', topic_selection восстановлен', Log::INFO, 'com_kunenatopic2article');

            // Сохраняем first_post_id как topic_id (для активации кнопки Create)
            $this->app->setUserState('com_kunenatopic2article.topic_id', $originalTopicId);
            Log::add('Установлен topic_id в сессии: ' . $originalTopicId, Log::INFO, 'com_kunenatopic2article');

            // Отправляем форму в таблицу
            $table = new ParamsTable($this->db);

            if (!$table->load(1)) {
                Log::add('Ошибка загрузки таблицы параметров: ' . Text::_('JLIB_DATABASE_ERROR_LOAD_FAILED'), Log::ERROR, 'com_kunenatopic2article');
                $this->app->enqueueMessage(Text::_('COM_KUNENATOPIC2ARTICLE_SAVE_FAILED') . ': ' . Text::_('JLIB_DATABASE_ERROR_LOAD_FAILED'), 'error');
                return false;
            }

            $table->bind($data);

            if (!$table->check() || !$table->store()) {
                Log::add('Ошибка сохранения в таблицу: ' . $table->getError(), Log::ERROR, 'com_kunenatopic2article');
                $this->app->enqueueMessage(Text::_('COM_KUNENATOPIC2ARTICLE_SAVE_FAILED') . ': ' . $table->getError(), 'error');
                return false;
            }

            // Устанавливаем успешное состояние для активации кнопки Create
            $this->app->setUserState('com_kunenatopic2article.save.success', true);
            Log::add('Сохранение успешно, save.success = true', Log::INFO, 'com_kunenatopic2article');
            return true;
        } else {
            // Сообщение об ошибке с originalTopicId
            $originalTopicId = !empty($data['topic_selection']) && is_numeric($data['topic_selection']) ? (int)$data['topic_selection'] : 0;
            Log::add('Ошибка валидации, originalTopicId: ' . $originalTopicId, Log::ERROR, 'com_kunenatopic2article');
            $this->app->enqueueMessage(Text::sprintf('COM_KUNENATOPIC2ARTICLE_ERROR_INVALID_TOPIC_ID', $originalTopicId), 'error');
            $data['topic_selection'] = ''; // Сбрасываем Topic ID в форме
            $this->app->setUserState('com_kunenatopic2article.topic_id', 0); // Сбрасываем topic_id
            Log::add('Topic ID сброшен на пустую строку', Log::WARNING, 'com_kunenatopic2article');
            return false;
        }
    }

    public function reset()
    {
        Log::add('Начало reset', Log::DEBUG, 'com_kunenatopic2article');

        $table = new ParamsTable($this->db);

        if (!$table->load(1)) {
            Log::add('Ошибка загрузки таблицы для reset: ' . Text::_('JLIB_DATABASE_ERROR_LOAD_FAILED'), Log::ERROR, 'com_kunenatopic2article');
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
            Log::add('Ошибка сохранения дефолтных значений: ' . $table->getError(), Log::ERROR, 'com_kunenatopic2article');
            $this->app->enqueueMessage(Text::_('COM_KUNENATOPIC2ARTICLE_RESET_FAILED') . ': ' . $table->getError(), 'error');
            return false;
        }

        $this->app->setUserState('com_kunenatopic2article.save.success', false);
        $this->app->setUserState('com_kunenatopic2article.topic_id', 0);
        Log::add('Reset успешно выполнен', Log::INFO, 'com_kunenatopic2article');
        return true;
    }

    public function create()
    {
        Log::add('Начало create', Log::DEBUG, 'com_kunenatopic2article');
        $this->app->setUserState('com_kunenatopic2article.save.success', false);
        $this->app->setUserState('com_kunenatopic2article.topic_id', 0);
        Log::add('Create успешно выполнен', Log::INFO, 'com_kunenatopic2article');
        return true;
    }
}
