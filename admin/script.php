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

        // Выполняем SQL-скрипт для создания таблицы и вставки данных
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
