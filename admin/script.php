<?php
defined('_JEXEC') or die;

class ComKunenatopic2articleInstallerScript
{
    public function install($parent)
    {
        JFactory::getApplication()->enqueueMessage('Installer script started', 'message');
        
        $db = JFactory::getDbo();
        $sqlfile = JPATH_ADMINISTRATOR . '/components/com_kunenatopic2article/sql/install.mysql.utf8.sql';
        
        JFactory::getApplication()->enqueueMessage('Starting installation process', 'message');

        // Выполняем SQL-скрипт для создания таблицы
        if (file_exists($sqlfile)) {
            JFactory::getApplication()->enqueueMessage('SQL file found: ' . $sqlfile, 'message');
            $sql = file_get_contents($sqlfile);
            $sql = str_replace('#__', $db->getPrefix(), $sql);
            $queries = $db->splitSql($sql);
            
            foreach ($queries as $query) {
                $query = trim($query);
                if ($query != '') {
                    $db->setQuery($query);
                    try {
                        $db->execute();
                        JFactory::getApplication()->enqueueMessage('Query executed successfully: ' . $query, 'message');
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
            
            // Проверяем, есть ли уже записи
            $query = $db->getQuery(true);
            $query->select('COUNT(*)')
                  ->from($db->quoteName('#__kunenatopic2article_params'));
            $db->setQuery($query);
            $count = $db->loadResult();
            JFactory::getApplication()->enqueueMessage('Records in table: ' . $count, 'message');
            
            // Если записей нет, добавляем одну
            if ($count == 0) {
                JFactory::getApplication()->enqueueMessage('No records found, inserting default params', 'message');
                $query = $db->getQuery(true);
                $columns = [
                    'topic_selection', 'article_category', 'post_transfer_scheme', 'max_article_size',
                    'post_author', 'post_creation_date', 'post_creation_time', 'post_ids',
                    'post_title', 'kunena_post_link', 'reminder_lines', 'ignored_authors'
                ];
                $values = [
                    0, 0, 1, 40000, 1, $db->quote(date('Y-m-d H:i:s')), $db->quote(date('Y-m-d H:i:s')),
                    1, 0, 0, 0, $db->quote(NULL)
                ];
                $query->insert($db->quoteName('#__kunenatopic2article_params'))
                      ->columns($columns)
                      ->values(implode(',', $values));
                $db->setQuery($query);
                try {
                    $db->execute();
                    JFactory::getApplication()->enqueueMessage('Default params inserted successfully', 'message');
                } catch (Exception $e) {
                    JFactory::getApplication()->enqueueMessage('Error inserting default params: ' . $e->getMessage(), 'error');
                }
            } else {
                JFactory::getApplication()->enqueueMessage('Table already contains records, skipping insert', 'message');
            }
        } else {
            JFactory::getApplication()->enqueueMessage('Table #__kunenatopic2article_params does not exist after creation attempt', 'error');
        }

        JFactory::getApplication()->enqueueMessage('Installer script completed', 'message');
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
