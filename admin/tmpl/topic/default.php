<?php
\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Layout\LayoutHelper;

// Получение приложения и флага для активации кнопки "Create Articles"
$app = Factory::getApplication();
$canCreate = $app->getUserState('com_kunenatopic2article.can_create', false);

// Подключение необходимых скриптов
HTMLHelper::_('bootstrap.framework');
HTMLHelper::_('behavior.keepalive');
HTMLHelper::_('behavior.formvalidator');
?>

<div class="container-fluid">
    <!-- Заголовок с синей полосой как на изображении -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0"><?php echo Text::_('COM_KUNENATOPIC2ARTICLE_POST_INFO'); ?></h4>
        </div>
        <div class="card-body">
            <h2><?php echo Text::_('COM_KUNENATOPIC2ARTICLE'); ?></h2>
            
            <form action="<?php echo Route::_('index.php?option=com_kunenatopic2article'); ?>" method="post" name="adminForm" id="adminForm" class="form-validate">
                
                <!-- Кнопки управления вверху -->
                <div class="mb-4">
                    <button type="submit" name="task" value="topic.remember" class="btn btn-primary me-2">
                        <?php echo Text::_('COM_KUNENATOPIC2ARTICLE_BUTTON_REMEMBER'); ?>
                    </button>
                    <button type="submit" name="task" value="topic.reset" class="btn btn-secondary me-2">
                        <?php echo Text::_('COM_KUNENATOPIC2ARTICLE_BUTTON_RESET_PARAMETERS'); ?>
                    </button>
                    <button type="submit" name="task" value="topic.create" class="btn btn-success me-2"
                        <?php echo !$canCreate ? 'disabled' : ''; ?>>
                        <?php echo Text::_('COM_KUNENATOPIC2ARTICLE_BUTTON_CREATE_ARTICLES'); ?>
                    </button>
                </div>

                <h3><?php echo Text::_('COM_KUNENATOPIC2ARTICLE_ARTICLE_PARAMS'); ?></h3>
                
                <!-- Article Parameters Section -->
                <div class="row mb-4">
                    <!-- Topic Selection -->
                    <div class="col-md-6 mb-3">
                        <label for="topic_selection" class="form-label">
                            <?php echo Text::_('COM_KUNENATOPIC2ARTICLE_TOPIC_SELECTION'); ?>
                        </label>
                        <select name="jform[topic_selection]" id="topic_selection" class="form-select">
                            <option value="">
                                <?php echo Text::_('COM_KUNENATOPIC2ARTICLE_TOPIC_SELECTION'); ?>
                            </option>
                        </select>
                        <small class="form-text text-muted">
                            <?php echo Text::_('COM_KUNENATOPIC2ARTICLE_TOPIC_SELECTION_DESC'); ?>
                        </small>
                    </div>

                    <!-- Article Category -->
                    <div class="col-md-6 mb-3">
                        <label for="article_category" class="form-label">
                            <?php echo Text::_('COM_KUNENATOPIC2ARTICLE_ARTICLE_CATEGORY'); ?>
                        </label>
                        <select name="jform[article_category]" id="article_category" class="form-select">
                            <option value="Uncategorised" <?php echo ($this->params->article_category ?? '') === 'Uncategorised' ? 'selected' : ''; ?>>
                                <?php echo Text::_('JGLOBAL_UNCATEGORISED'); ?>
                            </option>
                        </select>
                        <small class="form-text text-muted">
                            <?php echo Text::_('COM_KUNENATOPIC2ARTICLE_ARTICLE_CATEGORY_DESC'); ?>
                        </small>
                    </div>
                </div>

                <div class="row mb-4">
                    <!-- Post Transfer Scheme -->
                    <div class="col-md-6 mb-3">
                        <label for="post_transfer_scheme" class="form-label">
                            <?php echo Text::_('COM_KUNENATOPIC2ARTICLE_POST_TRANSFER_SCHEME'); ?>
                        </label>
                        <select name="jform[post_transfer_scheme]" id="post_transfer_scheme" class="form-select">
                            <option value="sequential" <?php echo ($this->params->post_transfer_scheme ?? '') === 'sequential' ? 'selected' : ''; ?>>
                                <?php echo Text::_('COM_KUNENATOPIC2ARTICLE_SEQUENTIAL'); ?>
                            </option>
                            <option value="threaded" <?php echo ($this->params->post_transfer_scheme ?? '') === 'threaded' ? 'selected' : ''; ?>>
                                <?php echo Text::_('COM_KUNENATOPIC2ARTICLE_THREADED'); ?>
                            </option>
                        </select>
                        <small class="form-text text-muted">
                            <?php echo Text::_('COM_KUNENATOPIC2ARTICLE_POST_TRANSFER_SCHEME_DESC'); ?>
                        </small>
                    </div>

                    <!-- Max Article Size -->
                    <div class="col-md-6 mb-3">
                        <label for="max_article_size" class="form-label bg-primary text-white px-2 py-1 rounded">
                            <?php echo Text::_('COM_KUNENATOPIC2ARTICLE_MAX_ARTICLE_SIZE'); ?>
                        </label>
                        <input type="number" name="jform[max_article_size]" id="max_article_size" 
                               class="form-control" value="<?php echo $this->params->max_article_size ?? '40000'; ?>" 
                               min="1000" max="100000">
                        <small class="form-text text-muted">
                            <?php echo Text::_('COM_KUNENATOPIC2ARTICLE_MAX_ARTICLE_SIZE_DESC'); ?>
                        </small>
                    </div>
                </div>

                <h3><?php echo Text::_('COM_KUNENATOPIC2ARTICLE_POST_INFO'); ?></h3>
                
                <!-- Post Information Section -->
                <div class="row mb-4">
                    <!-- Post Author -->
                    <div class="col-md-6 mb-3">
                        <label for="post_author" class="form-label">
                            <?php echo Text::_('COM_KUNENATOPIC2ARTICLE_POST_AUTHOR'); ?>
                        </label>
                        <select name="jform[post_author]" id="post_author" class="form-select">
                            <option value="show" <?php echo ($this->params->post_author ?? '') === 'show' ? 'selected' : ''; ?>>
                                <?php echo Text::_('COM_KUNENATOPIC2ARTICLE_INCLUDE'); ?>
                            </option>
                            <option value="hide" <?php echo ($this->params->post_author ?? '') === 'hide' ? 'selected' : ''; ?>>
                                <?php echo Text::_('COM_KUNENATOPIC2ARTICLE_NOT_INCLUDE'); ?>
                            </option>
                        </select>
                        <small class="form-text text-muted">
                            <?php echo Text::_('COM_KUNENATOPIC2ARTICLE_POST_AUTHOR_DESC'); ?>
                        </small>
                    </div>

                    <!-- Post Creation Date -->
                    <div class="col-md-6 mb-3">
                        <label for="post_creation_date" class="form-label">
                            <?php echo Text::_('COM_KUNENATOPIC2ARTICLE_POST_CREATION_DATE'); ?>
                        </label>
                        <input type="date" name="jform[post_creation_date]" id="post_creation_date" 
                               class="form-control" value="<?php echo $this->params->post_creation_date ?? '2025-03-14'; ?>">
                        <small class="form-text text-muted">
                            <?php echo Text::_('COM_KUNENATOPIC2ARTICLE_POST_CREATION_DATE_DESC'); ?>
                        </small>
                    </div>
                </div>

                <div class="row mb-4">
                    <!-- Post Creation Time -->
                    <div class="col-md-6 mb-3">
                        <label for="post_creation_time" class="form-label">
                            <?php echo Text::_('COM_KUNENATOPIC2ARTICLE_POST_CREATION_TIME'); ?>
                        </label>
                        <input type="datetime-local" name="jform[post_creation_time]" id="post_creation_time" 
                               class="form-control" value="<?php echo $this->params->post_creation_time ?? '2025-03-14T21:51:29'; ?>">
                        <small class="form-text text-muted">
                            <?php echo Text::_('COM_KUNENATOPIC2ARTICLE_POST_CREATION_TIME_DESC'); ?>
                        </small>
                    </div>

                    <!-- Post IDs -->
                    <div class="col-md-6 mb-3">
                        <label for="post_ids" class="form-label">
                            <?php echo Text::_('COM_KUNENATOPIC2ARTICLE_POST_IDS'); ?>
                        </label>
                        <select name="jform[post_ids]" id="post_ids" class="form-select">
                            <option value="show" <?php echo ($this->params->post_ids ?? '') === 'show' ? 'selected' : ''; ?>>
                                <?php echo Text::_('COM_KUNENATOPIC2ARTICLE_SHOW_IDS'); ?>
                            </option>
                            <option value="hide" <?php echo ($this->params->post_ids ?? '') === 'hide' ? 'selected' : ''; ?>>
                                <?php echo Text::_('COM_KUNENATOPIC2ARTICLE_HIDE_IDS'); ?>
                            </option>
                        </select>
                        <small class="form-text text-muted">
                            <?php echo Text::_('COM_KUNENATOPIC2ARTICLE_POST_IDS_DESC'); ?>
                        </small>
                    </div>
                </div>

                <!-- Дополнительные поля из языкового файла -->
                <div class="row mb-4">
                    <!-- Post Title -->
                    <div class="col-md-6 mb-3">
                        <label for="post_title" class="form-label">
                            <?php echo Text::_('COM_KUNENATOPIC2ARTICLE_POST_TITLE'); ?>
                        </label>
                        <select name="jform[post_title]" id="post_title" class="form-select">
                            <option value="show" <?php echo ($this->params->post_title ?? '') === 'show' ? 'selected' : ''; ?>>
                                <?php echo Text::_('COM_KUNENATOPIC2ARTICLE_POST_TITLE_SHOW'); ?>
                            </option>
                            <option value="hide" <?php echo ($this->params->post_title ?? '') === 'hide' ? 'selected' : ''; ?>>
                                <?php echo Text::_('COM_KUNENATOPIC2ARTICLE_POST_TITLE_HIDE'); ?>
                            </option>
                        </select>
                        <small class="form-text text-muted">
                            <?php echo Text::_('COM_KUNENATOPIC2ARTICLE_POST_TITLE_DESC'); ?>
                        </small>
                    </div>

                    <!-- Kunena Post Link -->
                    <div class="col-md-6 mb-3">
                        <label for="kunena_post_link" class="form-label">
                            <?php echo Text::_('COM_KUNENATOPIC2ARTICLE_KUNENA_POST_LINK'); ?>
                        </label>
                        <select name="jform[kunena_post_link]" id="kunena_post_link" class="form-select">
                            <option value="show" <?php echo ($this->params->kunena_post_link ?? '') === 'show' ? 'selected' : ''; ?>>
                                <?php echo Text::_('COM_KUNENATOPIC2ARTICLE_KUNENA_POST_LINK_ADD'); ?>
                            </option>
                            <option value="hide" <?php echo ($this->params->kunena_post_link ?? '') === 'hide' ? 'selected' : ''; ?>>
                                <?php echo Text::_('COM_KUNENATOPIC2ARTICLE_KUNENA_POST_LINK_NO'); ?>
                            </option>
                        </select>
                        <small class="form-text text-muted">
                            <?php echo Text::_('COM_KUNENATOPIC2ARTICLE_KUNENA_POST_LINK_DESC'); ?>
                        </small>
                    </div>
                </div>

                <div class="row mb-4">
                    <!-- Topic ID - изменяем на числовое поле -->
                    <div class="col-md-6 mb-3">
                        <label for="topic_id" class="form-label">
                            <?php echo Text::_('COM_KUNENATOPIC2ARTICLE_TOPIC_SELECTION'); ?>
                        </label>
                        <input type="number" name="jform[topic_id]" id="topic_id" 
                               class="form-control" value="<?php echo $this->params->topic_id ?? ''; ?>" 
                               min="1" placeholder="<?php echo Text::_('COM_KUNENATOPIC2ARTICLE_TOPIC_SELECTION'); ?>">
                        <small class="form-text text-muted">
                            <?php echo Text::_('COM_KUNENATOPIC2ARTICLE_TOPIC_SELECTION_DESC'); ?>
                        </small>
                    </div>

                    <!-- Ignored Authors -->
                    <div class="col-md-6 mb-3">
                        <label for="ignored_authors" class="form-label">
                            <?php echo Text::_('COM_KUNENATOPIC2ARTICLE_IGNORED_AUTHORS'); ?>
                        </label>
                        <input type="text" name="jform[ignored_authors]" id="ignored_authors" 
                               class="form-control" value="<?php echo $this->params->ignored_authors ?? ''; ?>"
                               placeholder="username1, username2, username3">
                        <small class="form-text text-muted">
                            <?php echo Text::_('COM_KUNENATOPIC2ARTICLE_IGNORED_AUTHORS_DESC'); ?>
                        </small>
                    </div>
                </div>

                <!-- Reminder Lines -->
                <div class="row mb-4">
                    <div class="col-md-6 mb-3">
                        <label for="reminder_lines" class="form-label">
                            <?php echo Text::_('COM_KUNENATOPIC2ARTICLE_REMINDER_LINES'); ?>
                        </label>
                        <input type="number" name="jform[reminder_lines]" id="reminder_lines" 
                               class="form-control" value="<?php echo $this->params->reminder_lines ?? '50'; ?>" 
                               min="0" max="300">
                        <small class="form-text text-muted">
                            <?php echo Text::_('COM_KUNENATOPIC2ARTICLE_REMINDER_LINES_DESC'); ?>
                        </small>
                    </div>
                </div>

                <!-- Кнопки управления внизу (дублирование для удобства) -->
                <div class="mt-4 pt-3 border-top">
                    <button type="submit" name="task" value="topic.remember" class="btn btn-primary me-2">
                        <?php echo Text::_('COM_KUNENATOPIC2ARTICLE_BUTTON_REMEMBER'); ?>
                    </button>
                    <button type="submit" name="task" value="topic.reset" class="btn btn-warning me-2">
                        <?php echo Text::_('COM_KUNENATOPIC2ARTICLE_BUTTON_RESET_PARAMETERS'); ?>
                    </button>
                    <button type="submit" name="task" value="topic.create" class="btn btn-success me-2"
                        <?php echo !$canCreate ? 'disabled' : ''; ?>>
                        <?php echo Text::_('COM_KUNENATOPIC2ARTICLE_BUTTON_CREATE_ARTICLES'); ?>
                    </button>
                </div>

                <!-- Скрытые поля -->
                <input type="hidden" name="option" value="com_kunenatopic2article">
                <input type="hidden" name="controller" value="topic">
                <?php echo HTMLHelper::_('form.token'); ?>
            </form>
        </div>
    </div>
</div>

<style>
/* Дополнительные стили для соответствия дизайну на изображении */
.card-header.bg-primary {
    background-color: #4a6fa5 !important;
}

.form-label.bg-primary {
    background-color: #4a6fa5 !important;
}

.btn-primary {
    background-color: #4a6fa5;
    border-color: #4a6fa5;
}

.btn-primary:hover {
    background-color: #3a5a95;
    border-color: #3a5a95;
}

.form-control, .form-select {
    border: 1px solid #ced4da;
    border-radius: 0.375rem;
}

.form-control:focus, .form-select:focus {
    border-color: #4a6fa5;
    box-shadow: 0 0 0 0.2rem rgba(74, 111, 165, 0.25);
}
</style>
