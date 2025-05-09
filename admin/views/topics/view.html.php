<?php
defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

class KunenaTopic2ArticleViewTopics extends JViewLegacy
{
    public function display($tpl = null)
    {
        // Загружаем модель
        $model = $this->getModel();

        // Получаем параметры из модели
        $this->parameters = $model->getParameters();

        // Устанавливаем заголовок страницы
        JToolbarHelper::title(Text::_('COM_KUNENATOPIC2ARTICLE_VIEW_DEFAULT_TITLE'), 'stack');

        parent::display($tpl);
    }
}
