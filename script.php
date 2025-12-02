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
    // Сохраняем в сессии
    Factory::getApplication()->setUserState('kunena_install_message', 
        Text::_('COM_KUNENATOPIC2ARTICLE_INSTALL_SUCCESS'));
    
    // JavaScript для отображения после загрузки
    echo '<script>
        document.addEventListener("DOMContentLoaded", function() {
            // Ждем пока появится модальное окно Joomla
            setTimeout(function() {
                var modal = document.querySelector(".modal-body");
                if (modal) {
                    // Создаем наше сообщение
                    var msg = document.createElement("div");
                    msg.className = "alert alert-success kunena-custom-message";
                    msg.innerHTML = "<strong>' . Text::_('COM_KUNENATOPIC2ARTICLE_INSTALL_SUCCESS') . '</strong>";
                    msg.style.marginTop = "15px";
                    msg.style.borderLeft = "4px solid #28a745";
                    
                    // Добавляем в модальное окно
                    modal.appendChild(msg);
                }
            }, 300);
        });
    </script>';
    
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
