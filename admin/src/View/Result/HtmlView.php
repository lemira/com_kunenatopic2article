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
    
// 1. Логирование ОТЛАДКА
        error_log('Debug: Reached Result View');
        error_log('All messages: '.print_r($app->getMessageQueue(), true));

// 2. Извлекаем данные из flash-сообщения
      $data = null;
        foreach ($app->getMessageQueue() as $message) {
            if ($message['type'] === 'kunena-result-data') {
                $data = json_decode($message['message'], true);
                break;
        }
    }

    // 3. Если данных нет - критическая ошибка
    if (!$data) {
        throw new \RuntimeException('ViewResult data not found in flash messages');
    }

    // 4. Устанавливаем данные для отображения
    $this->articles = $resultData['articles'];
    $this->emailsSent = $resultData['emails']['sent'];
    $this->emailsSentTo = $resultData['emails']['recipients'];
    
    parent::display($tpl);
   
}

}
