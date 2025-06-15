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
     $this->subject = ''; // Инициализируем subject

    
        try {
            $query = $this->db->getQuery(true)
                ->select(['subject'])
                ->from($this->db->quoteName('#__kunena_topics'))
                ->where($this->db->quoteName('first_post_id') . ' = ' . (int) $topicId)
                ->where($this->db->quoteName('hold') . ' = 0');

            $this->db->setQuery($query);

            $topic = $this->db->loadAssoc();
           
            if (!$topic) {
        //         throw new \Exception(Text::sprintf('COM_KUNENATOPIC2ARTICLE_ERROR_INVALID_TOPIC_ID', $topicId));
                 throw new \RuntimeException("Topic with ID {$topicId} does not exist or is not the first post of a topic.");
       }
            
            $this->subject = $topic['subject'] ?? '';
            return $topic;
            
        } catch (\Exception $e) {
            $this->app->enqueueMessage($e->getMessage(), 'error');
            return null;
        }
    }

    public function save($data)
    {
        $originalTopicId = !empty($data['topic_selection']) && is_numeric($data['topic_selection']) ? (int)$data['topic_selection'] : 0;
        
        if ($originalTopicId <= 0) {
            $this->app->enqueueMessage('Topic ID должно быть числом больше 0', 'error');
            return false;
        }
    
       $this->getTopicData($originalTopicId);
       
        if ($this->subject !== '') {
            // Возвращаем originalTopicId в Topic ID перед сохранением
            $data['topic_selection'] = $originalTopicId;

        // Сохраняем topic_id для отображения заголовка
        $this->app->setUserState('com_kunenatopic2article.topic_id', $topicId);

        // Сохраняем все данные, включая topic_selection как число
        $table = new ParamsTable($this->db);

        if (!$table->load(1)) {
            $this->app->enqueueMessage(Text::_('COM_KUNENATOPIC2ARTICLE_SAVE_FAILED') . ': ' . Text::_('JLIB_DATABASE_ERROR_LOAD_FAILED'), 'error');
            return false;
        }

        $table->bind($data);

      //??  if (!$table->check() || !$table->store()) {
            if (!$table->store()) {
            $this->app->enqueueMessage(Text::_('COM_KUNENATOPIC2ARTICLE_SAVE_FAILED') . ': ' . $table->getError(), 'error');
            return false;
        }

        // Устанавливаем успешное состояние для активации кнопки Create
       if ($this->app->setUserState('com_kunenatopic2article.save.success', true))
        return true;
       } else {
            // Сообщение об ошибке с originalTopicId
            $this->app->enqueueMessage(Text::sprintf('COM_KUNENATOPIC2ARTICLE_ERROR_INVALID_TOPIC_ID', $originalTopicId), 'error');
            // ПРАВИЛЬНО очищаем данные формы
            $data['topic_selection'] = 0; // Сбрасываем Topic ID в форме
            $this->app->setUserState('com_kunenatopic2article.topic_id', 0); // Сбрасываем topic_id  ??
            
        //?    $formData = $this->app->getUserState('com_kunenatopic2article.edit.topic.data', []);
        //? $this->app->setUserState('com_kunenatopic2article.edit.topic.data', $formData);
        
        // ?? Сбрасываем состояния
        $this->app->setUserState('com_kunenatopic2article.save.success', false);
       
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
