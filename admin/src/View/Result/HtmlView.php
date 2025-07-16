<?php
// Файл: admin/src/View/Result/HtmlView.php
namespace Joomla\Component\KunenaTopic2Article\Administrator\View\Result;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Factory;

class HtmlView extends BaseHtmlView
{
    protected $links;

   public function display($tpl = null): void
{
    /** @var \Joomla\Component\KunenaTopic2Article\Administrator\Model\ArticleModel $model */
    $model = $this->getModel('Article', 'Administrator'); // ✅ явное указание

    $this->articleLinks = $model->getState('articleLinks');
    $this->emailsSent   = $model->emailsSent ?? false;
    $this->emailsSentTo = $model->emailsSentTo ?? [];

    parent::display($tpl);
}
}
