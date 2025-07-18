<?php
/**
 * @package     KunenaTopic2Article
 * @subpackage  Administrator
 */
namespace Joomla\Component\KunenaTopic2Article\Administrator\Controller;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

class DisplayController extends BaseController
{
    protected $default_view = 'topic';

    public function display($cachable = false, $urlparams = [])
    {
        $app = Factory::getApplication();
        
        // Обработка сообщений после редиректа
        if ($redirectData = $app->getUserState('com_kunenatopic2article.redirect_data')) {
            $app->enqueueMessage($redirectData['message'], $redirectData['type']);
            $app->setUserState('com_kunenatopic2article.redirect_data', null);
        }

        // Сбрасываем флаг создания только при прямом доступе к view
        if ($app->input->getMethod() === 'GET') {
            $app->setUserState('com_kunenatopic2article.can_create', true);
        }

        return parent::display($cachable, $urlparams);
    }

    public function getModel($name = '', $prefix = '', $config = [])
    {
        if (empty($name)) {
            $name = $this->input->get('view', $this->default_view);
        }
        return parent::getModel($name, '', $config);
    }

    public function save()
    {
     // ТЕСТ    $this->checkToken();
        $model = $this->getModel('Topic');
        $data = $this->input->get('jform', [], 'array');

        if ($model->save($data)) {
            $message = Text::_('COM_KUNENATOPIC2ARTICLE_PARAMS_SAVED');
            $type = 'success';
            // Активируем кнопку Create после успешного сохранения
            Factory::getApplication()->setUserState('com_kunenatopic2article.can_create', true);
        } else {
            $message = Text::_('COM_KUNENATOPIC2ARTICLE_SAVE_FAILED');
            $type = 'error';
        }

        $this->setRedirect(
            Route::_('index.php?option=com_kunenatopic2article&view=topic', false),
            $message,
            $type
        );
    }

    public function reset()
    {
        $model = $this->getModel('Topic');
        if ($model->reset()) {
            $message = Text::_('COM_KUNENATOPIC2ARTICLE_PARAMS_RESET');
            $type = 'success';
        } else {
            $message = Text::_('COM_KUNENATOPIC2ARTICLE_RESET_FAILED');
            $type = 'error';
        }

        $this->setRedirect(
            Route::_('index.php?option=com_kunenatopic2article&view=topic', false),
            $message,
            $type
        );
    }

    public function create()
    {
      // ТЕСТ  $this->checkToken('post') or jexit(Text::_('JINVALID_TOKEN'));
        
        // Блокируем повторное создание
        Factory::getApplication()->setUserState('com_kunenatopic2article.can_create', false);

        // Редирект в ArticleController
        $this->setRedirect(
            Route::_('index.php?option=com_kunenatopic2article&task=article.create', false)
        );
    }
} // КОНЕЦ КЛАССА
