<?php
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;

echo '<h1>' . JText::_('COM_KUNENATOPIC2ARTICLE_ARTICLES_CREATED_SUCCESSFULLY') . '</h1>';

if (empty($this->links)) {
    echo '<p>' . JText::_('COM_KUNENATOPIC2ARTICLE_NO_ARTICLES_CREATED') . '</p>';
} else {
    echo '<ul>';
    foreach ($this->links as $link) {
        echo '<li><a href="' . htmlspecialchars($link['url'], ENT_QUOTES, 'UTF-8') . '" target="_blank">'
             . htmlspecialchars($link['title'], ENT_QUOTES, 'UTF-8') . '</a></li>';
    }
    echo '</ul>';
}
