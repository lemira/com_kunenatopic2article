<?php
// Файл: admin/src/View/Result/HtmlView.php
namespace Joomla\Component\KunenaTopic2Article\View\Result;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Factory;

class HtmlView extends BaseHtmlView
{
    protected $links;

    public function display($tpl = null)
    {
        $app = Factory::getApplication();
        $this->links = $app->getUserState('com_kunenatopic2article.article_links', []);
        parent::display($tpl);
    }
}
