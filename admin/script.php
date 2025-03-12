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
            
            // Проверяем, есть ли уже записи
            $query = $db->getQuery(true);
            $query->select('COUNT(*)')
                  ->from($db->quoteName('#__kunenatopic2article_params'));
            $db->setQuery($query);
            $count = $db->loadResult();
            JFactory::getApplication()->enqueueMessage('Records in table before insert: ' . $count, 'message');
            
            // Пробуем прямой SQL-запрос без использования Joomla API
            $query = "INSERT INTO " . $db->quoteName('#__kunenatopic2article_params') . " (
                topic_selection, article_category, post_transfer_scheme, max_article_size,
                post_author, post_creation_date, post_creation_time, post_ids,
                post_title, kunena_post_link, reminder_lines, ignored_authors
            ) VALUES (
                0, 0, 1, 40000, 1, 1, 0, 1, 0, 0, 0, NULL
            )";
            $db->setQuery($query);
            try {
                $db->execute();
                JFactory::getApplication()->enqueueMessage('Default params inserted successfully via raw query', 'message');
            } catch (Exception $e) {
                JFactory::getApplication()->enqueueMessage('Error inserting default params: ' . $e->getMessage(), 'error');
            }

            // Проверяем количество записей после вставки
            $query = $db->getQuery(true);
            $query->select('COUNT(*)')
                  ->from($db->quoteName('#__kunenatopic2article_params'));
            $db->setQuery($query);
            $countAfter = $db->loadResult();
            JFactory::getApplication()->enqueueMessage('Records in table after insert: ' . $countAfter, 'message');
            
            // Если не вставилось, пробуем вручную через другой способ
            if ($countAfter == 0) {
                $query = $db->getQuery(true);
                $columns = [
                    'topic_selection', 'article_category', 'post_transfer_scheme', 'max_article_size',
                    'post_author', 'post_creation_date', 'post_creation_time', 'post_ids',
                    'post_title', 'kunena_post_link', 'reminder_lines', 'ignored_authors'
                ];
                $values = [
                    0, 0, 1, 40000, 1, 1, 0, 1, 0, 0, 0, $db->quote(NULL)
                ];
                $query->insert($db->quoteName('#__kunenatopic2article_params'))
                      ->columns($columns)
                      ->values(implode(',', $values));
                $db->setQuery($query);
                try {
                    $db->execute();
                    JFactory::getApplication()->enqueueMessage('Default params inserted successfully via Joomla API', 'message');
                } catch (Exception $e) {
                    JFactory::getApplication()->enqueueMessage('Error inserting default params via Joomla API: ' . $e->getMessage(), 'error');
                }
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
