<?php
defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController;

class KunenaTopic2ArticleController extends BaseController
{
    public function display($cachable = false, $urlparams = [])
    {
        // Устанавливаем вьюху (topics) для отображения
        $view = $this->input->get('view', 'topics');
        $this->input->set('view', $view);
        return parent::display($cachable, $urlparams);
    }
}
