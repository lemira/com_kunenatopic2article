<?php
// Файл: admin/src/View/Topic/HtmlView.php
namespace Joomla\Component\KunenaTopic2Article\Administrator\View\Topic;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

class HtmlView extends BaseHtmlView
{
    protected $state;
    protected $form;
    protected $params;
    protected $paramsRemembered;

    public function display($tpl = null)
    {
        // Получаем данные из модели
        $this->form = $this->get('Form');
        $this->state = $this->get('State');
        
        // Получаем модель для дополнительных данных
        $model = $this->getModel();
        if ($model) {
            $this->params = $model->getParams();
            $this->paramsRemembered = $model->getParamsRemembered();
        }

        // Проверяем на ошибки
        if (count($errors = $this->get('Errors'))) {
            Factory::getApplication()->enqueueMessage(implode("\n", $errors), 'error');
            return false;
        }

        // Проверяем форму
        if (!$this->form) {
            Factory::getApplication()->enqueueMessage(Text::_('COM_KUNENATOPIC2ARTICLE_FORM_FAILED_TO_LOAD'), 'error');
        }

        parent::display($tpl);
    }
}
