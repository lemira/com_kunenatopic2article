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
    
    // 1. Извлекаем данные из flash-сообщения
    $messages = $app->getMessageQueue();
    $data = null;
    
    foreach ($messages as $message) {
        if ($message['type'] === 'kunena-result-data') {
            $data = json_decode($message['message'], true);
            break;
        }
    }

    // 2. Если данных нет - редирект с ошибкой на начало комп-та
    if (!$data) {
        $app->enqueueMessage(Text::_('COM_KUNENATOPIC2ARTICLE_NO_RESULTS'), 'error');
        $app->redirect(Route::_('index.php?option=com_kunenatopic2article', false));
        return;
    }

    // 3. Устанавливаем данные для отображения
    $this->articles = $data['articles'] ?? [];
    $this->emailsSent = $data['emails']['sent'] ?? false;
    $this->emailsSentTo = $data['emails']['recipients'] ?? [];

    // 4. Очищаем ВСЕ сообщения, чтобы избежать дублирования
    $app->getMessageQueue(true);
    
    parent::display($tpl);
}

}
