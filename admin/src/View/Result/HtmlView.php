<?php
/**
 * @package     KunenaTopic2Article
 * @subpackage  Administrator
 */

namespace Joomla\Component\KunenaTopic2Article\Administrator\View\Result;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

class HtmlView extends BaseHtmlView
{
    protected $articles = [];
    protected $emailsSent = false;
    protected $emailsSentTo = [];

public function display($tpl = null): void
{
    $app = Factory::getApplication();
    
   // Получаем данные из сессии
    $data = $app->getUserState('com_kunenatopic2article.result_data');

    if ($data) {
            // Если данные получены - присваиваем их и очищаем хранилище
            $this->articles = $data['articles'] ?? [];
            $this->emailsSent = $data['emails']['sent'] ?? false;
            $this->emailsSentTo = $data['emails']['recipients'] ?? [];
            
            $app->setUserState('com_kunenatopic2article.result_data', null); 
        } else {
            // Если данных нет, показываем ошибку вместо перехода к началу (форма ввода)
            $app->enqueueMessage(Text::_('COM_KUNENATOPIC2ARTICLE_NO_RESULTS'), 'error');
             $app->redirect(Route::_('index.php?option=com_kunenatopic2article', false));
            return;
        }

    // Сбрасываем can_create, чтобы кнопка была деактивирована
        $app->setUserState('com_kunenatopic2article.can_create', false);
    
    parent::display($tpl);
}

}
