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
    // 1. Путь к нашей внутренней папке с composer.json
    $libPath = JPATH_ADMINISTRATOR . '/components/com_kunenatopic2article/libraries/bbcode';

    // 2. Если ещё не стоят зависимости – ставим
    if (!file_exists($libPath . '/vendor/autoload.php'))
    {
        $composer = $this->findComposer();   // ниже
        if (!$composer)
        {
            throw new \RuntimeException('Composer not found. Ask hoster to install it.');
        }

        // Командная строка
        $cmd = escapeshellcmd($composer) .
               ' install --no-dev --prefer-dist --working-dir=' .
               escapeshellarg($libPath);
        exec($cmd . ' 2>&1', $out, $code);

        if ($code !== 0)
        {
            throw new \RuntimeException('Composer install failed:' . implode("\n", $out));
        }
    }

    // 3. Подключаем автолоадер (теперь классы ChrisKonnertz доступны)
    require_once $libPath . '/vendor/autoload.php';

    // (можно вызвать здесь же clearRouterCache и т. д.)
}
    
private function findComposer()
{
    // 1. Глобальный composer
    $global = trim((string)shell_exec('which composer 2>/dev/null'));
    if ($global && is_executable($global)) return $global;

    // 2. composer.phar в корне сайта
    $local = JPATH_ROOT . '/composer.phar';
    if (file_exists($local)) return PHP_BINARY . ' ' . $local;

    return false;
}
    
}
