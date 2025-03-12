<?php
defined('_JEXEC') or die;

class ComKunenatopic2articleInstallerScript
{
    public function install($parent)
    {
        $db = JFactory::getDbo();
        $sqlfile = JPATH_ADMINISTRATOR . '/components/com_kunenatopic2article/sql/install.mysql.utf8.sql';
        
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
                    } catch (Exception $e) {
                        JFactory::getApplication()->enqueueMessage('Error executing query: ' . $e->getMessage(), 'error');
                    }
                }
            }
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
