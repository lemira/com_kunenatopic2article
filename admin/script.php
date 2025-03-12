<?php
defined('_JEXEC') or die;

class ComKunenatopic2articleInstallerScript
{
    public function install($parent)
    {
        $db = JFactory::getDbo();
        $sqlfile = JPATH_ADMINISTRATOR . '/components/com_kunenatopic2article/sql/install.mysql.utf8.sql';
        
        // Выполняем SQL-скрипт для создания таблицы и вставки данных
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
                        JFactory::getApplication()->enqueueMessage('Query executed successfully: ' . $query, 'message');
                    } catch (Exception $e) {
                        JFactory::getApplication()->enqueueMessage('Error executing query: ' . $e->getMessage(), 'error');
                    }
                }
            }
        } else {
            JFactory::getApplication()->enqueueMessage('SQL file not found: ' . $sqlfile, 'error');
        }

        // Проверяем количество записей в таблице
        if ($db->tableExists('#__kunenatopic2article_params')) {
            $query = $db->getQuery(true);
            $query->select('COUNT(*)')
                  ->from($db->quoteName('#__kunenatopic2article_params'));
            $db->setQuery($query);
            $count = $db->loadResult();
            JFactory::getApplication()->enqueueMessage('Records in table after installation: ' . $count, 'message');
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
