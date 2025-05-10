<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

class KunenaTopic2ArticleViewTopics extends HtmlView
{
    protected $form;
    protected $item;

    public function display($tpl = null)
    {
        Log::add('Starting display method in KunenaTopic2ArticleViewTopics', Log::DEBUG, 'com_kunenatopic2article');

        // Загружаем форму
        $this->form = $this->get('Form');
        if (!$this->form) {
            Log::add('Failed to load form in KunenaTopic2ArticleViewTopics', Log::ERROR, 'com_kunenatopic2article');
            throw new Exception(Text::_('Failed to load form'), 500);
        }
        Log::add('Form loaded successfully', Log::DEBUG, 'com_kunenatopic2article');

        // Загружаем данные
        $this->item = $this->get('Item');
        if (!$this->item) {
            Log::add('Failed to load item in KunenaTopic2ArticleViewTopics', Log::ERROR, 'com_kunenatopic2article');
            throw new Exception(Text::_('Failed to load item'), 500);
        }
        Log::add('Item loaded successfully', Log::DEBUG, 'com_kunenatopic2article');

        // Проверяем ошибки
        if (count($errors = $this->get('Errors'))) {
            Log::add('Errors found: ' . implode("\n", $errors), Log::ERROR, 'com_kunenatopic2article');
            throw new Exception(implode("\n", $errors), 500);
        }

        $this->addToolbar();

        Log::add('Calling parent::display in KunenaTopic2ArticleViewTopics', Log::DEBUG, 'com_kunenatopic2article');
        parent::display($tpl);
    }

    protected function addToolbar()
    {
        ToolbarHelper::title(Text::_('COM_KUNENATOPIC2ARTICLE'), 'article');
        ToolbarHelper::custom('topic.save', 'save', 'save', 'Save', false);
        ToolbarHelper::custom('topic.reset', 'cancel', 'cancel', 'Reset Parameters', false);
        ToolbarHelper::custom('topic.createArticles', 'publish', 'publish', 'Create Articles', false);
    }
}
