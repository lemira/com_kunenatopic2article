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

        if (empty($data)) {
            $app->enqueueMessage(Text::_('COM_KUNENATOPIC2ARTICLE_NO_RESULTS'), 'error');
            $app->redirect(Route::_('index.php?option=com_kunenatopic2article', false));
            return;
        }

        // Устанавливаем данные для представления
        $this->articles = $data['articles'];
        $this->emailsSent = $data['emails']['sent'] ?? false;
        $this->emailsSentTo = $data['emails']['recipients'] ?? [];

        parent::display($tpl);
   } 
}
