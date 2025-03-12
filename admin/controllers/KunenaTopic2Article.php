<?php
defined('_JEXEC') or die;

class KunenaTopic2ArticleController extends JControllerForm
{
    public function __construct($config = array())
    {
        parent::__construct($config);
    }

    public function display($cachable = false, $urlparams = false)
    {
        $view = $this->getView('kunenatopic2article', 'html');
        $model = $this->getModel('kunenatopic2article');
        $view->setModel($model, true);
        $view->display();
    }
}
