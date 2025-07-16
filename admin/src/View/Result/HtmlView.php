namespace Joomla\Component\KunenaTopic2Article\Administrator\View\Result;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

class HtmlView extends BaseHtmlView
{
    public function display($tpl = null): void
    {
        try {
            // Явно указываем администраторскую модель
            $model = $this->getModel('Article', 'Administrator');
            
            if (!$model) {
                throw new \RuntimeException(
                    Text::sprintf('COM_KUNENATOPIC2ARTICLE_MODEL_NOT_FOUND', 'Article')
                );
            }

            // Безопасное получение данных
            $this->articleLinks = (array) $model->getState('articleLinks', []);
            $this->emailsSent = (bool) $model->getState('emailsSent', false);
            $this->emailsSentTo = (array) $model->getState('emailsSentTo', []);
            
            parent::display($tpl);
            
        } catch (\Exception $e) {
            Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
        }
    }
}
