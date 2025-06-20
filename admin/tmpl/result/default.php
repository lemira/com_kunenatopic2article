<?php
defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

// Указываем IDE, что $this — HtmlView
/** @var Joomla\Component\KunenaTopic2Article\Administrator\View\Result\HtmlView $this */
?>

<div class="joomla-overview">
    <h1><?php echo Text::_('COM_KUNENATOPIC2ARTICLE_RESULT_HEADING'); ?></h1>

    <?php if (!empty($this->links)) : ?>
        <div class="alert alert-success">
            <p><?php echo Text::_('COM_KUNENATOPIC2ARTICLE_ARTICLES_CREATED'); ?></p>
            <ul>
                <?php foreach ($this->links as $link) : ?>
                    <li>
                        <a href="<?php echo $link['url']; ?>" target="_blank">
                            <?php echo htmlspecialchars($link['title'], ENT_QUOTES, 'UTF-8'); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php else : ?>
        <div class="alert alert-info">
            <p><?php echo Text::_('COM_KUNENATOPIC2ARTICLE_NO_ARTICLES_CREATED'); ?></p>
        </div>
    <?php endif; ?>

    <?php if ($this->emailsSent ?? false) : ?>
        <div class="alert alert-warning">
            <p><?php echo Text::_('COM_KUNENATOPIC2ARTICLE_EMAILS_SENT_NOTICE'); ?></p>
        </div>
    <?php endif; ?>

    <div class="button-container" style="margin-top: 2rem;">
        <a class="btn btn-primary" href="<?php echo Route::_('index.php?option=com_kunenatopic2article'); ?>">
            <?php echo Text::_('COM_KUNENATOPIC2ARTICLE_CONTINUE_WORK'); ?>
        </a>
        <a class="btn btn-secondary" href="<?php echo Route::_('index.php'); ?>">
            <?php echo Text::_('COM_KUNENATOPIC2ARTICLE_FINISH_WORK'); ?>
        </a>
    </div>
</div>
