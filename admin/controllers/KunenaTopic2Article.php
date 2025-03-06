<?php
defined('_JEXEC') or die;
use Joomla\CMS\MVC\Controller\AdminController;
class KunenaTopic2ArticleController extends AdminController
{
    protected $default_view = 'topics';
    public function getModel($name = 'Topic', $prefix = 'KunenaTopic2ArticleModel', $config = array('ignore_request' => true))
    {
        return parent::getModel($name, $prefix, $config);
    }
}
