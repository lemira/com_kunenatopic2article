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
            Log::ALL, // Включаем все уровни логирования
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
            $message = 'Ошибка загрузки формы: ' . Text::_('COM_KUNENATOPIC2ARTICLE_FORM_LOAD_ERROR');
            Log::add($message, Log::ERROR, 'com_kunenatopic2article');
            $this->app->enqueueMessage($message, 'error');
            return null;
        }

        $message = 'Форма успешно загружена';
        Log::add($message, Log::INFO, 'com_kunenatopic2article');
        $this->app->enqueueMessage($message, 'notice');
        return $form;
    }

    protected function loadFormData(): array
    {
        $data = $this->app->getUserState('com_kunenatopic2article.edit.topic.data', []);

        $message = 'Начало загрузки данных формы. Текущие данные: ' . print_r($data, true);
        Log::add($message, Log::DEBUG, 'com_kunenatopic2article');
        $this->app->enqueueMessage($message, 'debug');

        if (empty($data)) {
            $params = $this->getParams();
            $data = $params ? $params->getProperties() : [];
            $message = 'Данные из параметров: ' . print_r($data, true);
            Log::add($message, Log::DEBUG, 'com_kunenatopic2article');
            $this->app->enqueueMessage($message, 'debug');
            $topicId = $this->app->getUserState('com_kunenatopic2article.topic_id', 0);
            $message = 'Topic ID из сессии: ' . $topicId;
            Log::add($message, Log::DEBUG, 'com_kunenatopic2article');
            $this->app->enqueueMessage($message, 'debug');
            if ($topicId) {
                $this->getTopicData($topicId); // Заполняем $subject
                $message = 'После getTopicData, subject: ' . $this->subject;
                Log::add($message, Log::DEBUG, 'com_kunenatopic2article');
                $this->app->enqueueMessage($message, 'debug');
                if ($this->subject) {
                    $data['topic_selection'] = $this->subject; // Показываем subject в форме
                    $message = 'Установлен topic_selection: ' . $this->subject;
                    Log::add($message, Log::INFO, 'com_kunenatopic2article');
                    $this->app->enqueueMessage($message, 'notice');
                } else {
                    $data['topic_selection'] = ''; // Если не найдено, ставим пустую строку
                    $message = 'Тема не найдена, topic_selection сброшен на пустую строку';
                    Log::add($message, Log::WARNING, 'com_kunenatopic2article');
                    $this->app->enqueueMessage($message, 'warning');
                }
            }
        }

        $message = 'Данные формы после обработки: ' . print_r($data, true);
        Log::add($message, Log::DEBUG, 'com_kunenatopic2article');
        $this->app->enqueueMessage($message, 'debug');
        return $data;
    }

    public function getParams(): ?ParamsTable
    {
        $table = new ParamsTable($this->db);

        if (!$table->load(1)) {
            $message = 'Ошибка загрузки параметров: ' . Text::_('JLIB_DATABASE_ERROR_LOAD_FAILED');
            Log::add($message, Log::ERROR, 'com_kunenatopic2article');
            $this->app->enqueueMessage($message, 'error');
            return null;
        }

        $message = 'Параметры успешно загружены';
        Log::add($message, Log::INFO, 'com_kunenatopic2article');
        $this->app->enqueueMessage($message, 'notice');
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
        $message = 'Начало getTopicData. originalTopicId: ' . $originalTopicId;
        Log::add($message, Log::DEBUG, 'com_kunenatopic2article');
        $this->app->enqueueMessage($message, 'debug');

        try {
            $query = $this->db->getQuery(true)
                ->select(['subject'])
                ->from($this->db->quoteName('#__kunena_topics'))
                ->where($this->db->quoteName('first_post_id') . ' = ' . $this->db->quote((int)$originalTopicId))
                ->where($this->db->quoteName('hold') . ' = 0');

            $message = 'SQL-запрос: ' . $query->dump();
            Log::add($message, Log::DEBUG, 'com_kunenatopic2article');
            $this->app->enqueueMessage($message, 'debug');

            $result = $this->db->setQuery($query)->loadObject();
            $message = 'Результат SQL (raw): ' . print_r($result, true);
            Log::add($message, Log::DEBUG, 'com_kunenatopic2article');
            $this->app->enqueueMessage($message, 'debug');

            if ($result) {
                $this->subject = $result->subject; // Присваиваем subject
                $message = 'Тема найдена. subject: ' . $this->subject;
                Log::add($message, Log::INFO, 'com_kunenatopic2article');
                $this->app->enqueueMessage($message, 'notice');
                $this->app->setUserState('com_kunenatopic2article.edit.topic.data.topic_selection', $this->subject); // Обновляем форму через сессию
                $message = 'Обновлено topic_selection в сессии: ' . $this->subject;
                Log::add($message, Log::INFO, 'com_kunenatopic2article');
                $this->app->enqueueMessage($message, 'notice');
            } else {
                $message = 'Тема не найдена для first_post_id: ' . $originalTopicId;
                Log::add($message, Log::WARNING, 'com_kunenatopic2article');
                $this->app->enqueueMessage($message, 'warning');
            }
        } catch (\Exception $e) {
            $message = 'Ошибка в getTopicData: ' . $e->getMessage();
            Log::add($message, Log::ERROR, 'com_kunenatopic2article');
            $this->app->enqueueMessage($message, 'error');
        }

        $message = 'Конец getTopicData. subject: ' . $this->subject;
        Log::add($message, Log::DEBUG, 'com_kunenatopic2article');
        $this->app->enqueueMessage($message, 'debug');
    }

    public function save($data)
    {
        $message = 'Начало save. Входные данные: ' . print_r($data, true);
        Log::add($message, Log::DEBUG, 'com_kunenatopic2article');
        $this->app->enqueueMessage($message, 'debug');

        if (empty($data)) {
            $message = 'Данные пусты, сохранение прервано';
            Log::add($message, Log::ERROR, 'com_kunenatopic2article');
            $this->app->enqueueMessage($message, 'error');
            return false;
        }

        // Вызываем getTopicData
        $this->getTopicData(!empty($data['topic_selection']) && is_numeric($data['topic_selection']) ? (int)$data['topic_selection'] : 0);
        $message = 'После getTopicData, subject: ' . $this->subject;
        Log::add($message, Log::DEBUG, 'com_kunenatopic2article');
        $this->app->enqueueMessage($message, 'debug');

        // Проверяем $subject
        if ($this->subject !== '') {
            // Возвращаем originalTopicId в Topic ID перед сохранением
            $originalTopicId = !empty($data['topic_selection']) && is_numeric($data['topic_selection']) ? (int)$data['topic_selection'] : 0;
            $data['topic_selection'] = $originalTopicId;
            $message = 'Успешная проверка, originalTopicId: ' . $originalTopicId . ', topic_selection восстановлен';
            Log::add($message, Log::INFO, 'com_kunenatopic2article');
            $this->app->enqueueMessage($message, 'notice');

            // Сохраняем first_post_id как topic_id (для активации кнопки Create)
            $this->app->setUserState('com_kunenatopic2article.topic_id', $originalTopicId);
            $message = 'Установлен topic_id в сессии: ' . $originalTopicId;
            Log::add($message, Log::INFO, 'com_kunenatopic2article');
            $this->app->enqueueMessage($message, 'notice');

            // Отправляем форму в таблицу
            $table = new ParamsTable($this->db);

            if (!$table->load(1)) {
                $message = 'Ошибка загрузки таблицы параметров: ' . Text::_('JLIB_DATABASE_ERROR_LOAD_FAILED');
                Log::add($message, Log::ERROR, 'com_kunenatopic2article');
                $this->app->enqueueMessage($message, 'error');
                return false;
            }

            $table->bind($data);

            if (!$table->check() || !$table->store()) {
                $message = 'Ошибка сохранения в таблицу: ' . $table->getError();
                Log::add($message, Log::ERROR, 'com_kunenatopic2article');
                $this->app->enqueueMessage($message, 'error');
                return false;
            }

            // Устанавливаем успешное состояние для активации кнопки Create
            $this->app->setUserState('com_kunenatopic2article.save.success', true);
            $message = 'Сохранение успешно, save.success = true';
            Log::add($message, Log::INFO, 'com_kunenatopic2article');
            $this->app->enqueueMessage($message, 'notice');
            return true;
        } else {
            // Сообщение об ошибке с originalTopicId
            $originalTopicId = !empty($data['topic_selection']) && is_numeric($data['topic_selection']) ? (int)$data['topic_selection'] : 0;
            $message = 'Ошибка валидации, originalTopicId: ' . $originalTopicId;
            Log::add($message, Log::ERROR, 'com_kunenatopic2article');
            $this->app->enqueueMessage($message, 'error');
            $data['topic_selection'] = ''; // Сбрасываем Topic ID в форме
            $this->app->setUserState('com_kunenatopic2article.topic_id', 0); // Сбрасываем topic_id
            $message = 'Topic ID сброшен на пустую строку';
            Log::add($message, Log::WARNING, 'com_kunenatopic2article');
            $this->app->enqueueMessage($message, 'warning');
            return false;
        }
    }

    public function reset()
    {
        $message = 'Начало reset';
        Log::add($message, Log::DEBUG, 'com_kunenatopic2article');
        $this->app->enqueueMessage($message, 'debug');

        $table = new ParamsTable($this->db);

        if (!$table->load(1)) {
            $message = 'Ошибка загрузки таблицы для reset: ' . Text::_('JLIB_DATABASE_ERROR_LOAD_FAILED');
            Log::add($message, Log::ERROR, 'com_kunenatopic2article');
            $this->app->enqueueMessage($message, 'error');
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
            $message = 'Ошибка сохранения дефолтных значений: ' . $table->getError();
            Log::add($message, Log::ERROR, 'com_kunenatopic2article');
            $this->app->enqueueMessage($message, 'error');
            return false;
        }

        $this->app->setUserState('com_kunenatopic2article.save.success', false);
        $this->app->setUserState('com_kunenatopic2article.topic_id', 0);
        $message = 'Reset успешно выполнен';
        Log::add($message, Log::INFO, 'com_kunenatopic2article');
        $this->app->enqueueMessage($message, 'notice');
        return true;
    }

    public function create()
    {
        $message = 'Начало create';
        Log::add($message, Log::DEBUG, 'com_kunenatopic2article');
        $this->app->enqueueMessage($message, 'debug');
        $this->app->setUserState('com_kunenatopic2article.save.success', false);
        $this->app->setUserState('com_kunenatopic2article.topic_id', 0);
        $message = 'Create успешно выполнен';
        Log::add($message, Log::INFO, 'com_kunenatopic2article');
        $this->app->enqueueMessage($message, 'notice');
        return true;
    }
}
