<?php
defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController;

class KunenaTopic2ArticleController extends BaseController
{
    protected $default_view = 'topics';

    public function display($cachable = false, $urlparams = false)
    {
        $this->setRedirect('index.php?option=com_kunenatopic2article&view=topics');
        $this->redirect();
    }
}
