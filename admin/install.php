<?php
defined('_JEXEC') or die;

class com_kunenatopic2articleInstallerScript
{
    public function install($parent)
    {
        $db = JFactory::getDbo();

        // Читаем SQL-файл
        $sqlFile = JPATH_ADMINISTRATOR . '/components/com_kunenatopic2article/sql/install.mysql.utf8.sql';
        if (!file_exists($sqlFile)) {
            JFactory::getApplication()->enqueueMessage('SQL file not found: ' . $sqlFile, 'error');
            return false;
        }

        $sql = file_get_contents($sqlFile);
        if ($sql === false) {
            JFactory::getApplication()->enqueueMessage('Failed to read SQL file: ' . $sqlFile, 'error');
            return false;
        }

        // Разбиваем SQL на отдельные запросы
        $queries = $db->splitSql($sql);
        foreach ($queries as $query) {
            $query = trim($query);
            if (!empty($query)) {
                $db->setQuery($query);
                try {
                    $db->execute();
                } catch (Exception $e) {
                    JFactory::getApplication()->enqueueMessage('SQL Error: ' . $e->getMessage(), 'error');
                    return false;
                }
            }
        }

        JFactory::getApplication()->enqueueMessage('Kunena Topic to Article installed successfully', 'message');
        return true;
    }

    public function uninstall($parent)
    {
        $db = JFactory::getDbo();

        // Читаем SQL-файл для удаления
        $sqlFile = JPATH_ADMINISTRATOR . '/components/com_kunenatopic2article/sql/uninstall.mysql.utf8.sql';
        if (!file_exists($sqlFile)) {
            JFactory::getApplication()->enqueueMessage('Uninstall SQL file not found: ' . $sqlFile, 'warning');
            return true; // Не прерываем удаление, если файл не найден
        }

        $sql = file_get_contents($sqlFile);
        if ($sql === false) {
            JFactory::getApplication()->enqueueMessage('Failed to read uninstall SQL file: ' . $sqlFile, 'warning');
            return true;
        }

        $queries = $db->splitSql($sql);
        foreach ($queries as $query) {
            $query = trim($query);
            if (!empty($query)) {
                $db->setQuery($query);
                try {
                    $db->execute();
                } catch (Exception $e) {
                    JFactory::getApplication()->enqueueMessage('Uninstall SQL Error: ' . $e->getMessage(), 'warning');
                    return true;
                }
            }
        }

        return true;
    }
}
