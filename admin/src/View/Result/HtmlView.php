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
    // Загружаем модель явно, если она не загружена автоматически
    $model = $this->getModel('Article');

    if (!$model) {
        throw new \RuntimeException('Model not found');
    }

    $this->articleLinks = $model->getState('articleLinks', []); // Добавляем значение по умолчанию
    $this->emailsSent   = $model->getState('emailsSent', false);
    $this->emailsSentTo = $model->getState('emailsSentTo', []);

    parent::display($tpl);
}
}
