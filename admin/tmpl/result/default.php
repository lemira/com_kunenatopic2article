<?php
defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var \Joomla\Component\KunenaTopic2Article\Administrator\View\Result\HtmlView $this */
?>

<div class="container-fluid">
    <h1><?php echo Text::_('COM_KUNENATOPIC2ARTICLE_RESULT_HEADING'); ?></h1>

    <?php if (is_array($this->links) && !empty($this->links)) : ?>
        <div class="alert alert-success">
            <p><?php echo Text::_('COM_KUNENATOPIC2ARTICLE_ARTICLES_CREATED'); ?></p>
            <ul class="list-group">
                <?php foreach ($this->links as $link) : ?>
                    <li class="list-group-item">
                        <a href="<?php echo $this->escape($link['url']); ?>" target="_blank">
                            <?php echo $this->escape($link['title']); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php else : ?>
        <div class="alert alert-info">
            <?php echo Text::_('COM_KUNENATOPIC2ARTICLE_NO_ARTICLES_CREATED'); ?>
        </div>
    <?php endif; ?>

    <?php if ($this->emailsSent) : ?>
        <div class="alert alert-info mt-3">
            <h3><?php echo Text::_('COM_KUNENATOPIC2ARTICLE_EMAILS_SENT'); ?></h3>
            <?php if (is_array($this->emailsSentTo) && !empty($this->emailsSentTo)) : ?>
                <p><?php echo Text::_('COM_KUNENATOPIC2ARTICLE_RECIPIENTS_LIST'); ?></p>
                <ul>
                    <?php foreach ($this->emailsSentTo as $email) : ?>
                        <li><?php echo $this->escape($email); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="mt-4">
        <a href="<?php echo Route::_('index.php?option=com_kunenatopic2article'); ?>" 
           class="btn btn-primary">
            <?php echo Text::_('COM_KUNENATOPIC2ARTICLE_CONTINUE_WORK'); ?>
        </a>
        <a href="<?php echo Route::_('index.php'); ?>" 
           class="btn btn-secondary ms-2">
            <?php echo Text::_('COM_KUNENATOPIC2ARTICLE_FINISH_WORK'); ?>
        </a>
    </div>
</div>
