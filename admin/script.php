<?php
defined('_JEXEC') or die;

class com_kunenatopic2articleInstallerScript
{
    public function preflight($type, $parent)
    {
        // Можно добавить проверки перед установкой, если нужно
    }

    public function postflight($type, $parent)
    {
        // Можно добавить действия после установки, если нужно
    }

    public function uninstall($parent)
    {
        // Удаление таблицы при деинсталляции
        $db = JFactory::getDbo();
        $db->setQuery('DROP TABLE IF EXISTS `#__kunenatopic2article_params`');
        $db->execute();
    }
}
