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
        
        // ИЗМЕНЕНО: используем first_post_id вместо topic_id
        $firstPostId = $this->app->getUserState('com_kunenatopic2article.first_post_id', 0);
        if ($firstPostId) {
            $this->getTopicData($firstPostId); // Заполняем $subject
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
 * @param int $firstPostId - ID первого поста темы
 */
protected function getTopicData($firstPostId)
{
    $this->subject = ''; // Инициализируем subject
    
    try {
        $query = $this->db->getQuery(true)
            ->select(['subject'])
            ->from($this->db->quoteName('#__kunena_topics'))
            ->where($this->db->quoteName('first_post_id') . ' = ' . $this->db->quote((int)$firstPostId))
            ->where($this->db->quoteName('hold') . ' = 0');

        $result = $this->db->setQuery($query)->loadObject();

        if ($result) {
            $this->subject = $result->subject; // Присваиваем subject
        }

    } catch (\Exception $e) {
        $this->app->enqueueMessage($e->getMessage(), 'error');
    }
}

public function save($data)
{
    // Получаем first_post_id из формы
    $firstPostId = !empty($data['topic_selection']) && is_numeric($data['topic_selection']) ? (int)$data['topic_selection'] : 0;
    
    if ($firstPostId <= 0) {
        $this->app->enqueueMessage('Topic ID должно быть числом больше 0', 'error');
        return false;
    }

    // Вызываем getTopicData для проверки темы
    $this->getTopicData($firstPostId);

    // Проверяем, найдена ли тема (subject не пустой)
    if ($this->subject !== '') {
        // Тема найдена - сохраняем first_post_id обратно в данные
        $data['topic_selection'] = $firstPostId;

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

        // ИЗМЕНЕНО: сохраняем first_post_id вместо topic_id
        $this->app->setUserState('com_kunenatopic2article.save.success', true);
        $this->app->setUserState('com_kunenatopic2article.first_post_id', $firstPostId);
        return true;
        
    } else {
        // Тема не найдена - выводим ошибку с first_post_id
        $this->app->enqueueMessage(Text::sprintf('COM_KUNENATOPIC2ARTICLE_ERROR_INVALID_TOPIC_ID', $firstPostId), 'error');
        $data['topic_selection'] = ''; // Сбрасываем Topic ID в форме
        $this->app->setUserState('com_kunenatopic2article.first_post_id', 0); // Сбрасываем first_post_id
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

    // ИЗМЕНЕНО: сбрасываем first_post_id вместо topic_id
    $this->app->setUserState('com_kunenatopic2article.save.success', false);
    $this->app->setUserState('com_kunenatopic2article.first_post_id', 0);
    return true;
}

public function create()
{
    // ИЗМЕНЕНО: сбрасываем first_post_id вместо topic_id
    $this->app->setUserState('com_kunenatopic2article.save.success', false);
    $this->app->setUserState('com_kunenatopic2article.first_post_id', 0);
    return true;
}
