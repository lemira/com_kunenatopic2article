<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var \Joomla\Component\KunenaTopic2Article\Administrator\View\Result\HtmlView $this */

$app = Factory::getApplication();
?>
<div class="container-fluid">
      <h2><?php echo Text::_('COM_KUNENATOPIC2ARTICLE_RESULTS_TITLE'); ?></h2>

    <?php if (!empty($this->articles)) : ?>
        <div class="alert alert-success">
            <h3><?php echo Text::_('COM_KUNENATOPIC2ARTICLE_CREATED_ARTICLES'); ?></h3>
            <ul class="list-group">
                <?php foreach ($this->articles as $article) : ?>
                    <li class="list-group-item">
                        <a href="<?php echo $this->escape($article['url']); ?>" target="_blank">
                            <?php echo $this->escape($article['title']); ?>
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
            <?php if (!empty($this->emailsSentTo)) : ?>
                <p><?php echo Text::_('COM_KUNENATOPIC2ARTICLE_RECIPIENTS_LIST'); ?></p>
                <ul class="list-unstyled">
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
