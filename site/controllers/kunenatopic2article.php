<?php
defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController;

class KunenaTopic2ArticleController extends BaseController
{
    protected $default_view = 'topics';

    public function getModel($name = 'Topic', $prefix = 'KunenaTopic2ArticleModel', $config = array('ignore_request' => true))
    {
        return parent::getModel($name, $prefix, $config);
    }
}
