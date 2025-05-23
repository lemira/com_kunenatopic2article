<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

class com_kunenatopic2articleInstallerScript
{
    /**
     * Выполняется перед установкой, обновлением или деинсталляцией
     *
     * @param string $type Тип действия (install, update, uninstall)
     * @param object $parent Объект установщика
     * @return bool
     */
    public function preflight($type, $parent)
    {
        // Можно добавить проверки перед установкой, если нужно
        return true;
    }

    /**
     * Выполняется при установке компонента
     *
     * @param object $parent Объект установщика
     * @return bool
     */
    public function install($parent)
    {
        try {
            $db = Factory::getDatabase();

            // Читаем SQL-файл
            $sqlFile = JPATH_COMPONENT_ADMINISTRATOR . '/sql/install.mysql.utf8.sql';
            if (!file_exists($sqlFile)) {
                Factory::getApplication()->enqueueMessage(
                    Text::sprintf('COM_KUNENATOPIC2ARTICLE_SQL_FILE_NOT_FOUND', $sqlFile),
                    'error'
                );
                return false;
            }

            $sql = file_get_contents($sqlFile);
            if ($sql === false) {
                Factory::getApplication()->enqueueMessage(
                    Text::sprintf('COM_KUNENATOPIC2ARTICLE_SQL_FILE_READ_FAILED', $sqlFile),
                    'error'
                );
                return false;
            }

            // Разбиваем SQL на отдельные запросы
            $queries = $db->splitSql($sql);
            foreach ($queries as $query) {
                $query = trim($query);
                if (!empty($query)) {
                    $db->setQuery($query);
                    $db->execute();
                }
            }

            Factory::getApplication()->enqueueMessage(
                Text::_('COM_KUNENATOPIC2ARTICLE_INSTALL_SUCCESS'),
                'success'
            );
            return true;
        } catch (\Exception $e) {
            Factory::getApplication()->enqueueMessage(
                Text::sprintf('COM_KUNENATOPIC2ARTICLE_SQL_ERROR', $e->getMessage()),
                'error'
            );
            return false;
        }
    }

    /**
     * Выполняется после установки или обновления
     *
     * @param string $type Тип действия (install, update)
     * @param object $parent Объект установщика
     * @return void
     */
    public function postflight($type, $parent)
    {
        // Можно добавить действия после установки, если нужно
    }

    /**
     * Выполняется при деинсталляции компонента
     *
     * @param object $parent Объект установщика
     * @return bool
     */
    public function uninstall($parent)
    {
        try {
            $db = Factory::getDatabase();

            // Читаем SQL-файл для удаления
            $sqlFile = JPATH_COMPONENT_ADMINISTRATOR . '/sql/uninstall.mysql.utf8.sql';
            if (!file_exists($sqlFile)) {
                Factory::getApplication()->enqueueMessage(
                    Text::sprintf('COM_KUNENATOPIC2ARTICLE_UNINSTALL_SQL_FILE_NOT_FOUND', $sqlFile),
                    'warning'
                );
                // Продолжаем деинсталляцию, так как отсутствие файла не критично
            } else {
                $sql = file_get_contents($sqlFile);
                if ($sql === false) {
                    Factory::getApplication()->enqueueMessage(
                        Text::sprintf('COM_KUNENATOPIC2ARTICLE_UNINSTALL_SQL_FILE_READ_FAILED', $sqlFile),
                        'warning'
                    );
                } else {
                    $queries = $db->splitSql($sql);
                    foreach ($queries as $query) {
                        $query = trim($query);
                        if (!empty($query)) {
                            $db->setQuery($query);
                            $db->execute();
                        }
                    }
                }
            }

            // Дополнительно удаляем таблицу параметров (для надёжности)
            $db->setQuery('DROP TABLE IF EXISTS `#__kunenatopic2article_params`');
            $db->execute();

            Factory::getApplication()->enqueueMessage(
                Text::_('COM_KUNENATOPIC2ARTICLE_UNINSTALL_SUCCESS'),
                'success'
            );
            return true;
        } catch (\Exception $e) {
            Factory::getApplication()->enqueueMessage(
                Text::sprintf('COM_KUNENATOPIC2ARTICLE_UNINSTALL_ERROR', $e->getMessage()),
                'error'
            );
            return false;
        }
    }
}
