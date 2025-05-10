<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Table\Table;

class KunenaTopic2ArticleModelTopic extends AdminModel
{
    public function getTable($type = 'KunenaArticle', $prefix = 'Table', $config = [])
    {
        $table = Table::getInstance($type, $prefix, $config);
        if (!$table) {
            Log::add('Failed to load table: ' . $type, Log::ERROR, 'com_kunenatopic2article');
            $this->setError(Text::_('Failed to load table: ' . $type));
            return false;
        }
        return $table;
    }

    public function getForm($data = [], $loadData = true)
    {
        $form = $this->loadForm('com_kunenatopic2article.topics', 'topics', ['control' => 'jform', 'load_data' => $loadData]);

        if (empty($form)) {
            Log::add('Failed to load form: com_kunenatopic2article.topics', Log::ERROR, 'com_kunenatopic2article');
            return false;
        }

        return $form;
    }

    protected function loadFormData()
    {
        $data = $this->getItem();
        return $data;
    }

    public function getItem($pk = null)
    {
        $table = $this->getTable();
        if (!$table) {
            Log::add('Table is null in getItem', Log::ERROR, 'com_kunenatopic2article');
            return false;
        }

        $table->load(1); // Загружаем запись с id=1, если она есть

        $properties = $table->getProperties();
        if (empty($properties)) {
            Log::add('Table properties are empty', Log::WARNING, 'com_kunenatopic2article');
        }

        return $properties;
    }

    public function save($data)
    {
        $table = $this->getTable();
        if (!$table) {
            return false;
        }

        $table->load(1); // Сохраняем всегда в запись с id=1

        // Проверка существования Kunena темы
        $db = Factory::getDbo();
        $query = $db->getQuery(true)
            ->select('id')
            ->from('#__kunena_topics')
            ->where('id = ' . (int) $data['topic_selection']);
        $db->setQuery($query);
        $topicExists = $db->loadResult();

        if (!$topicExists) {
            $this->setError(Text::_('Kunena topic does not exist'));
            return false;
        }

        if (!$table->bind($data)) {
            $this->setError($table->getError());
            return false;
        }

        if (!$table->store()) {
            $this->setError($table->getError());
            return false;
        }

        return true;
    }

    public function reset()
    {
        $db = Factory::getDbo();
        $query = $db->getQuery(true)
            ->delete('#__kunena_article')
            ->where('id = 1');
        $db->setQuery($query);

        try {
            $db->execute();
            return true;
        } catch (Exception $e) {
            $this->setError($e->getMessage());
            return false;
        }
    }
}
