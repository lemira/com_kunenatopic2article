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

public function postflight($type, $parent)
{
    $libPath = JPATH_ADMINISTRATOR . '/components/com_kunenatopic2article/libraries/bbcode';

    // 1. Проверяем, есть ли автолоадер
    if (!file_exists($libPath . '/vendor/autoload.php'))
    {
        // 2. Проверяем, есть ли composer.json
        if (!file_exists($libPath . '/composer.json'))
        {
            throw new \RuntimeException('Library descriptor missing: ' . $libPath . '/composer.json');
        }

        // 3. Находим исполняемый composer
        $composer = $this->findComposer();   // см. ниже
        if (!$composer)
        {
            throw new \RuntimeException('Composer not found. Please install Composer on the server.');
        }

        // 4. Устанавливаем зависимости
        $cmd = escapeshellcmd($composer) . ' install --no-dev --prefer-dist --working-dir=' . escapeshellarg($libPath);
        exec($cmd . ' 2>&1', $out, $code);

        if ($code !== 0)
        {
            throw new \RuntimeException('Composer install failed: ' . implode(PHP_EOL, $out));
        }
    }

    // 5. Подключаем автолоадер
    require_once $libPath . '/vendor/autoload.php';
}

private function findComposer()
{
    // 1. Смотрим глобальный composer
    $global = trim(shell_exec('which composer'));
    if ($global && is_executable($global)) return $global;

    // 2. Пробуем php composer.phar в корне сайта
    $local = JPATH_ROOT . '/composer.phar';
    if (file_exists($local) && is_executable($local)) return PHP_BINARY . ' ' . $local;

    return false;
}
    
}
