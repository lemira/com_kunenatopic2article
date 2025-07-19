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

    // Получаем данные из flash-сообщений
    foreach ($app->getMessageQueue() as $message) {
        if ($message['type'] === 'kunena-result-data') {
            $data = json_decode($message['message'], true);
            break;
        }
    }

    if (empty($data)) {
        $app->enqueueMessage(Text::_('COM_KUNENATOPIC2ARTICLE_NO_RESULTS'), 'error');
        $app->redirect(Route::_('index.php?option=com_kunenatopic2article', false));
        return;
    }

    // Назначаем данные для представления
    $this->articles = $data['articles'];
    $this->emailsSent = $data['emails']['sent'];
    $this->emailsSentTo = $data['emails']['recipients'];

    parent::display($tpl);
}
}
