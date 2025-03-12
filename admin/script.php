<?php
defined('_JEXEC') or die;

class ComKunenatopic2articleInstallerScript
{
    public function install($parent)
    {
        $db = JFactory::getDbo();
        $sqlfile = JPATH_ADMINISTRATOR . '/components/com_kunenatopic2article/sql/install.mysql.utf8.sql';
        
        // Выполняем SQL-скрипт для создания таблицы
        if (file_exists($sqlfile)) {
            $sql = file_get_contents($sqlfile);
            $sql = str_replace('#__', $db->getPrefix(), $sql);
            $queries = $db->splitSql($sql);
            
            foreach ($queries as $query) {
                $query = trim($query);
                if ($query != '') {
                    $db->setQuery($query);
                    try {
                        $db->execute();
                        JFactory::getApplication()->enqueueMessage('Table creation query executed successfully: ' . $query, 'message');
                    } catch (Exception $e) {
                        JFactory::getApplication()->enqueueMessage('Error executing query: ' . $e->getMessage(), 'error');
                    }
                }
            }
        } else {
            JFactory::getApplication()->enqueueMessage('SQL file not found: ' . $sqlfile, 'error');
        }

        // Проверяем, существует ли таблица
        if ($db->tableExists('#__kunenatopic2article_params')) {
            JFactory::getApplication()->enqueueMessage('Table #__kunenatopic2article_params exists', 'message');
            
            // Добавляем запись с параметрами по умолчанию
            $query = $db->getQuery(true);
            $query->insert($db->quoteName('#__kunenatopic2article_params'))
                  ->columns([
                      $db->quoteName('topic_selection'),
                      $db->quoteName('article_category'),
                      $db->quoteName('post_transfer_scheme'),
                      $db->quoteName('max_article_size'),
                      $db->quoteName('post_author'),
                      $db->quoteName('post_creation_date'),
                      $db->quoteName('post_creation_time'),
                      $db->quoteName('post_ids'),
                      $db->quoteName('post_title'),
                      $db->quoteName('kunena_post_link'),
                      $db->quoteName('reminder_lines'),
                      $db->quoteName('ignored_authors')
                  ])
                  ->values('0, 0, 1, 40000, 1, 1, 0, 1, 0, 0, 0, NULL');
            $db->setQuery($query);
            try {
                $db->execute();
                JFactory::getApplication()->enqueueMessage('Default params inserted successfully', 'message');
            } catch (Exception $e) {
                JFactory::getApplication()->enqueueMessage('Error inserting default params: ' . $e->getMessage(), 'error');
            }
        } else {
            JFactory::getApplication()->enqueueMessage('Table #__kunenatopic2article_params does not exist after creation attempt', 'error');
        }
    }

    public function uninstall($parent)
    {
    }

    public function update($parent)
    {
    }

    public function preflight($type, $parent)
    {
    }

    public function postflight($type, $parent)
    {
    }
}
