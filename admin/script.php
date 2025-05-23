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
     * @return void
     */
    public function preflight($type, $parent)
    {
        // Можно добавить проверки перед установкой, если нужно
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
     * @return void
     */
    public function uninstall($parent)
    {
        try {
            // Получаем объект базы данных
            $db = Factory::getDatabase();
            
            // Удаляем таблицу параметров
            $db->setQuery('DROP TABLE IF EXISTS `#__kunenatopic2article_params`');
            $db->execute();
            
            // Сообщение об успешной деинсталляции
            Factory::getApplication()->enqueueMessage(
                Text::_('COM_KUNENATOPIC2ARTICLE_TABLE_PARAMS_DROPPED'),
                'success'
            );
        } catch (\Exception $e) {
            // Сообщение об ошибке
            Factory::getApplication()->enqueueMessage(
                Text::sprintf('COM_KUNENATOPIC2ARTICLE_UNINSTALL_ERROR', $e->getMessage()),
                'error'
            );
        }
    }
}
