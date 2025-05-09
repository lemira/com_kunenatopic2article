<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

// Загружаем Bootstrap 5 CSS и JS
HTMLHelper::_('bootstrap.framework');

$model = $this->getModel();
$parameters = $model->getParameters();
?>

<style>
    .form-select {
        max-width: 300px;
    }
</style>

<div class="container">
    <h1><?php echo Text::_('COM_KUNENATOPIC2ARTICLE_VIEW_DEFAULT_TITLE'); ?></h1>
    <form action="<?php echo \Joomla\CMS\Router\Route::_('index.php?option=com_kunenatopic2article'); ?>" method="post" name="adminForm" id="adminForm">
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="topic_selection" class="form-label"><?php echo Text::_('COM_KUNENATOPIC2ARTICLE_FIELD_TOPIC_SELECTION'); ?></label>
                    <input type="number" name="jform[topic_selection]" id="topic_selection" class="form-control" value="<?php echo htmlspecialchars($parameters->topic_selection ?? 0); ?>" />
                </div>

                <div class="mb-3">
                    <label for="article_category" class="form-label"><?php echo Text::_('COM_KUNENATOPIC2ARTICLE_FIELD_ARTICLE_CATEGORY'); ?></label>
                    <?php
                    $options = HTMLHelper::_('category.options', 'com_content');
                    echo HTMLHelper::_('select.genericlist', $options, 'jform[article_category]', 'class="form-select"', 'value', 'text', $parameters->article_category ?? '');
                    ?>
                </div>

                <div class="mb-3">
                    <label class="form-label"><?php echo Text::_('COM_KUNENATOPIC2ARTICLE_FIELD_POST_TRANSFER_SCHEME'); ?></label>
                    <div class="form-check">
                        <input type="radio" name="jform[post_transfer_scheme]" id="post_transfer_scheme_sequential" value="sequential" class="form-check-input" <?php echo ($parameters->post_transfer_scheme ?? 'sequential') === 'sequential' ? 'checked' : ''; ?> />
                        <label for="post_transfer_scheme_sequential" class="form-check-label"><?php echo Text::_('COM_KUNENATOPIC2ARTICLE_FIELD_POST_TRANSFER_SCHEME_SEQUENTIAL'); ?></label>
                    </div>
                    <div class="form-check">
                        <input type="radio" name="jform[post_transfer_scheme]" id="post_transfer_scheme_threaded" value="threaded" class="form-check-input" <?php echo ($parameters->post_transfer_scheme ?? 'sequential') === 'threaded' ? 'checked' : ''; ?> />
                        <label for="post_transfer_scheme_threaded" class="form-check-label"><?php echo Text::_('COM_KUNENATOPIC2ARTICLE_FIELD_POST_TRANSFER_SCHEME_THREADED'); ?></label>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="max_article_size" class="form-label"><?php echo Text::_('COM_KUNENATOPIC2ARTICLE_FIELD_MAX_ARTICLE_SIZE'); ?></label>
                    <input type="number" name="jform[max_article_size]" id="max_article_size" class="form-control" value="<?php echo htmlspecialchars($parameters->max_article_size ?? 40000); ?>" />
                </div>

                <div class="mb-3">
                    <label class="form-label"><?php echo Text::_('Post Information'); ?></label>
                    <div class="form-check">
                        <input type="checkbox" name="jform[post_author]" id="post_author" value="1" class="form-check-input" <?php echo ($parameters->post_author ?? 1) ? 'checked' : ''; ?> />
                        <label for="post_author" class="form-check-label"><?php echo Text::_('COM_KUNENATOPIC2ARTICLE_FIELD_POST_AUTHOR'); ?></label>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="jform[post_creation_date]" id="post_creation_date" value="1" class="form-check-input" <?php echo ($parameters->post_creation_date ?? 0) ? 'checked' : ''; ?> />
                        <label for="post_creation_date" class="form-check-label"><?php echo Text::_('COM_KUNENATOPIC2ARTICLE_FIELD_POST_CREATION_DATE'); ?></label>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="jform[post_creation_time]" id="post_creation_time" value="1" class="form-check-input" <?php echo ($parameters->post_creation_time ?? 0) ? 'checked' : ''; ?> />
                        <label for="post_creation_time" class="form-check-label"><?php echo Text::_('COM_KUNENATOPIC2ARTICLE_FIELD_POST_CREATION_TIME'); ?></label>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="jform[post_ids]" id="post_ids" value="1" class="form-check-input" <?php echo ($parameters->post_ids ?? 0) ? 'checked' : ''; ?> />
                        <label for="post_ids" class="form-check-label"><?php echo Text::_('COM_KUNENATOPIC2ARTICLE_FIELD_POST_IDS'); ?></label>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="jform[post_title]" id="post_title" value="1" class="form-check-input" <?php echo ($parameters->post_title ?? 0) ? 'checked' : ''; ?> />
                        <label for="post_title" class="form-check-label"><?php echo Text::_('COM_KUNENATOPIC2ARTICLE_FIELD_POST_TITLE'); ?></label>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="jform[kunena_post_link]" id="kunena_post_link" value="1" class="form-check-input" <?php echo ($parameters->kunena_post_link ?? 0) ? 'checked' : ''; ?> />
                        <label for="kunena_post_link" class="form-check-label"><?php echo Text::_('COM_KUNENATOPIC2ARTICLE_FIELD_KUNENA_POST_LINK'); ?></label>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="reminder_lines" class="form-label"><?php echo Text::_('COM_KUNENATOPIC2ARTICLE_FIELD_REMINDER_LINES'); ?></label>
                    <input type="number" name="jform[reminder_lines]" id="reminder_lines" class="form-control" min="0" max="300" value="<?php echo htmlspecialchars($parameters->reminder_lines ?? 0); ?>" />
                </div>

                <div class="mb-3">
                    <label for="ignored_authors" class="form-label"><?php echo Text::_('COM_KUNENATOPIC2ARTICLE_FIELD_IGNORED_AUTHORS'); ?></label>
                    <input type="text" name="jform[ignored_authors]" id="ignored_authors" class="form-control" value="<?php echo htmlspecialchars($parameters->ignored_authors ?? ''); ?>" />
                </div>
            </div>
        </div>

        <div class="mt-3">
            <button type="submit" class="btn btn-primary" onclick="Joomla.submitbutton('topic.save')"><?php echo Text::_('COM_KUNENATOPIC2ARTICLE_BUTTON_SAVE'); ?></button>
            <button type="submit" class="btn btn-secondary" onclick="Joomla.submitbutton('topic.reset')"><?php echo Text::_('Reset Parameters'); ?></button>
            <button type="submit" class="btn btn-success" onclick="Joomla.submitbutton('topic.createarticles')"><?php echo Text::_('COM_KUNENATOPIC2ARTICLE_BUTTON_CREATE_ARTICLES'); ?></button>
        </div>

        <input type="hidden" name="task" value="" />
        <?php echo HTMLHelper::_('form.token'); ?>
    </form>
</div>
