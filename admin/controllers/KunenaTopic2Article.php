<?php
defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController;

class KunenaTopic2ArticleController extends BaseController
{
    public function display($cachable = false, $urlparams = [])
    {
        $this->setRedirect('index.php?option=com_kunenatopic2article&view=topics');
        return parent::display($cachable, $urlparams);
    }
}
