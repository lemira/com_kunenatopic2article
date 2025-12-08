<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_kunenatopic2article
 *
 * @copyright   (C) 2025 Leonid Ratner. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

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
        // Сначала получаем данные из сессии, если мы вернулись после неудачного сохранения
        $data = $this->app->getUserState('com_kunenatopic2article.edit.topic.data', []);

        // Если в сессии ничего нет (первая загрузка или после успешного сохранения)
        if (empty($data)) {
            // Загружаем последние сохраненные параметры из базы
            $params = $this->getTableParams();
            $data   = $params ? $params->getProperties() : [];
        }

        // проверяем, есть ли из сессии или из базы валидный ID темы
        $topicId = !empty($data['topic_selection']) ? (int) $data['topic_selection'] : 0;

        if ($topicId > 0) {
            // Пытаемся получить данные темы.
            // getTopicData сам обработает ошибку, если тема не найдется.
            $this->getTopicData($topicId);

            if ($this->subject) {
                // Если тема найдена, показываем в форме ее заголовок.
                // При сохранении мы все равно будем использовать ID.
                $data['topic_selection'] = $this->subject;
            }
        }

        return $data;
    }

    public function getTableParams(): ?ParamsTable
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
        // Получаем ID из формы
        $originalTopicId = !empty($data['topic_selection']) && is_numeric($data['topic_selection']) ? (int) $data['topic_selection'] : 0;
        
        if ($originalTopicId <= 0) {
            $this->app->enqueueMessage('Topic ID должно быть числом больше 0', 'error');
            return false;
        }
    
        // Получаем данные темы
        if ($this->getTopicData($originalTopicId) === null) {   // Если getTopicData успешно нашел тему, он заполнил $this->subject
            // Если getTopicData вернул ошибку, просто выходим
            return false;
        }
        
        // Возвращаем ID в данные для сохранения в базу
        $data['topic_selection'] = $originalTopicId;

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

     // НЕ НУЖНО?! - УБРАТЬ   $this->app->setUserState('com_kunenatopic2article.save.success', false);
     //   $this->app->setUserState('com_kunenatopic2article.topic_id', 0);
        return true;
    }
}
