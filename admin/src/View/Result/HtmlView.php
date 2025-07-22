namespace Joomla\Component\KunenaTopic2Article\Administrator\View\Result;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

class HtmlView extends BaseHtmlView
{
$app = Factory::getApplication();
error_log('All flash messages: '.print_r($app->getMessageQueue(), true));

    protected $articles = [];
    protected $emailsSent = false;
    protected $emailsSentTo = [];

public function display($tpl = null): void
{
    $app = Factory::getApplication();
    
   // Получаем данные из сессии
    $data = $app->getUserState('com_kunenatopic2article.result_data');

    if ($data) {
        $app->setUserState('com_kunenatopic2article.result_data', null); // Если данные получены - очищаем хранилище
    } else {
        throw new RuntimeException('No result data found');
    }

    $this->articles = $data['articles'];
    $this->emailsSent = $data['emails']['sent'];
    $this->emailsSentTo = $data['emails']['recipients'];
    
    parent::display($tpl);
}

}
