<?php
defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController;

class KunenaTopic2ArticleController extends BaseController
{
    protected $default_view = 'topics';

    public function display($cachable = false, $urlparams = false)
    {
        // Проверяем, есть ли параметр view в URL
        $view = $this->input->get('view');

        // Если view отсутствует или не равен 'topics', перенаправляем
        if (!$view || $view !== 'topics') {
            $this->setRedirect('index.php?option=com_kunenatopic2article&view=topics');
            $this->redirect();
        }

        // Если view=topics, отображаем представление
        return parent::display($cachable, $urlparams);
    }
}
