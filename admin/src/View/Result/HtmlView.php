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
    $data = null;
    
    // Извлекаем данные из flash-сообщения
    $messages = $app->getMessageQueue();
    foreach ($messages as $message) {
        if ($message['type'] === 'kunena-result-data') {
            $data = json_decode($message['message'], true);
            break;
        }
    }

    // Если данных нет - редирект из-за ошибки на начало ком-та
    if (!$data) {
        $app->enqueueMessage(Text::_('COM_KUNENATOPIC2ARTICLE_NO_RESULTS'), 'error');
        $this->setRedirect(Route::_('index.php?option=com_kunenatopic2article', false));
        return;
    }

    // Удаляем служебное сообщение, чтобы оно не выводилось
    $app->getMessageQueue(true); // Очищает все сообщения
    
    // Устанавливаем данные для отображения
    $this->articles = $data['articles'];
    $this->emailsSent = $data['emails']['sent'];
    $this->emailsSentTo = $data['emails']['recipients'];

    // Добавляем стандартное success-сообщение
    $app->enqueueMessage(Text::_('COM_KUNENATOPIC2ARTICLE_ARTICLES_CREATED_SUCCESS'), 'success');
    
    parent::display($tpl);
}
}
