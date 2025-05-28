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

    public function display($tpl = null)
    {
        $model = $this->getModel('Topic');
        if ($model === null) {
            Factory::getApplication()->enqueueMessage(Text::_('COM_KUNENATOPIC2ARTICLE_MODEL_FAILED_TO_LOAD'), 'error');
            $this->params = null;
            $this->state = null;
            $this->form = null;
        } else {
            $this->params = $model->getParams();
            $this->state = $model->getState();
            $this->form = $model->getForm();
        }

        if (!$this->form) {
            Factory::getApplication()->enqueueMessage(Text::_('COM_KUNENATOPIC2ARTICLE_FORM_FAILED_TO_LOAD'), 'error');
        }

        parent::display($tpl);
    }
}
