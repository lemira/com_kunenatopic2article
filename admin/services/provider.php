<?php
defined('_JEXEC') || exit;

use Joomla\Component\KunenaTopic2Article\Administrator\View\Result\HtmlView as ResultHtmlView;
use Joomla\Component\KunenaTopic2Article\Administrator\View\Topic\HtmlView as TopicHtmlView;

return [
    'views' => [
        'result.html' => ResultHtmlView::class,
        'topic.html' => TopicHtmlView::class,
    ],
];

/** <?php
defined('_JEXEC') || exit;
use Joomla\Component\KunenaTopic2Article\Administrator\Extension\KunenaTopic2ArticleServiceProvider;
return new KunenaTopic2ArticleServiceProvider;
**/
