<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Cache\Cache;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Language\Text;

class com_KunenaTopic2ArticleInstallerScript
{
   public function install($parent)
{
    $app = Factory::getApplication();
    
    $html = '
    <div style="margin: 15px 0; padding: 15px; background: #f8f9fa; border-left: 4px solid #28a745; border-radius: 4px;">
        <h4 style="margin-top: 0; color: #28a745;">✅ Kunena Topic to Article установлен!</h4>
        <p><strong>Компонент для конвертации тем Kunena Forum в статьи Joomla</strong></p>
        
        <div style="margin: 10px 0; padding: 10px; background: white; border: 1px solid #ddd;">
            <strong>🚀 Как начать:</strong>
            <ol style="margin: 5px 0 0 15px;">
                <li>Перейдите в <em>Компоненты → Kunena Topic to Article</em></li>
                <li>Выберите тему Kunena для конвертации</li>
                <li>Настройте параметры и создайте статью</li>
            </ol>
        </div>
        
        <p style="margin-top: 10px; font-size: 0.9em; color: #666;">
            <em>Благодарим за использование нашего компонента!</em>
        </p>
    </div>';
    
    $app->enqueueMessage($html, 'message');
    
    return true;
}
    
    public function update($parent) 
{
    echo '<p style="color: blue; font-weight: bold;">' . Text::_('COM_KUNENATOPIC2ARTICLE_UPDATE_SUCCESS') . '</p>';
    return true;
}
    
    public function uninstall($parent) 
    {
        $this->cleanMenuItems();
        $this->clearRouterCache();
        echo '<p style="color: orange; font-weight: bold;">' . Text::_('COM_KUNENATOPIC2ARTICLE_UNINSTALL_SUCCESS') . '</p>';
        return true;
    }
    
    public function preflight($type, $parent) 
    {
        if ($type === 'update') {
            echo '<p>' . Text::_('COM_KUNENATOPIC2ARTICLE_PREFLIGHT_UPDATE') . '</p>';
        }
        return true;
    }
    
    public function postflight($type, $parent) 
    {
        if ($type === 'install') {
            echo '<p>' . Text::_('COM_KUNENATOPIC2ARTICLE_POSTFLIGHT_INSTALL') . '</p>';
        }
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
            Log::add(Text::_('COM_KUNENATOPIC2ARTICLE_ERROR_CLEAN_MENU') . ': ' . $e->getMessage(), Log::WARNING, 'jerror');
        }
    }
    
    private function clearRouterCache()
    {
        try {
            Cache::getCacheController('callback')->clean('com_menus');
            Cache::getCacheController('callback')->clean('com_router');
        } catch (Exception $e) {
            Log::add(Text::_('COM_KUNENATOPIC2ARTICLE_ERROR_CLEAN_CACHE') . ': ' . $e->getMessage(), Log::WARNING, 'jerror');
        }
    }
}
