<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_kunenatopic2article
 *
 * @copyright   (C) 2025 Leonid Ratner. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Cache\Cache;
use Joomla\CMS\Log\Log;

class com_KunenaTopic2ArticleInstallerScript
{
    public function uninstall($parent) 
    {
        $this->cleanMenuItems();
        $this->clearRouterCache();
        return true;
    }
    
    private function cleanMenuItems()
    {
        try {
            $db = Factory::getDbo();
            $query = $db->getQuery(true);
            $query->delete($db->quoteName('#__menu'))
                  ->where($db->quoteName('link') . ' LIKE ' . $db->quote('%option=com_kunenatopic2article%'))
                  ->where($db->quoteName('type') . ' = ' . $db->quote('component'));
            $db->setQuery($query);
            $db->execute();
        } catch (Exception $e) {
            // Логируем ошибку, но не прерываем деинсталляцию
            Log::add('Error cleaning KunenaTopic2Article menu items: ' . $e->getMessage(), Log::WARNING, 'jerror');
        }
    }
    
    private function clearRouterCache()
{
    try {
        $app = Factory::getApplication();
        // чистим системный кэш
        $app->getCache()->clean('com_menus');
        $app->getCache()->clean('com_router');
    } catch (\Throwable $e) {
        Log::add('Error clearing KunenaTopic2Article router cache: ' . $e->getMessage(), Log::WARNING, 'jerror');
    }
}
