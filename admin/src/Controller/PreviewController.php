<?php
namespace Joomla\Component\Kunenatopic2article\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Factory;

class PreviewController extends BaseController
{
    public function display($cachable = false, $urlparams = array())
    {
        $app = Factory::getApplication();
        $id = $app->input->getInt('id');
        
        if (!$id) {
            throw new Exception('Article ID not specified');
        }
        
        // Получаем статью напрямую из БД, игнорируя состояние
        $db = Factory::getDbo();
        $query = $db->getQuery(true)
            ->select('*')
            ->from('#__content')
            ->where('id = ' . (int)$id);
        
        $db->setQuery($query);
        $article = $db->loadObject();
        
        if (!$article) {
            throw new Exception('Article not found');
        }
        
        // Просто рендерим статью
        header('Content-Type: text/html; charset=utf-8');
        echo $article->introtext . $article->fulltext;
        $app->close();
    }
}
