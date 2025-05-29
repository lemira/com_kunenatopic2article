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
            <h4 class="mb-0"><?php echo Text::_('COM_KUNENATOPIC2ARTICLE_RENDERING_POST_INFO_FIELDSET'); ?></h4>
        </div>
        <div class="card-body">
            <h2><?php echo Text::_('COM_KUNENATOPIC2ARTICLE_TOPIC_TO_ARTICLE_PARAMS'); ?></h2>
            
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

                <h3><?php echo Text::_('COM_KUNENATOPIC2ARTICLE_ARTICLE_PARAMETERS'); ?></h3>
                
                <!-- Article Parameters Section -->
                <div class="row mb-4">
                    <!-- Topic Selection -->
                    <div class="col-md-6 mb-3">
                        <label for="topic_selection" class="form-label">
                            <?php echo Text::_('COM_KUNENATOPIC2ARTICLE_TOPIC_SELECTION'); ?>
                        </label>
                        <select name="jform[topic_selection]" id="topic_selection" class="form-select">
                            <option value="COM_KUNENATOPIC2ARTICLE_ALL_TOPICS" <?php echo ($this->params->topic_selection ?? '') === 'COM_KUNENATOPIC2ARTICLE_ALL_TOPICS' ? 'selected' : ''; ?>>
                                <?php echo Text::_('COM_KUNENATOPIC2ARTICLE_ALL_TOPICS'); ?>
                            </option>
                        </select>
                        <small class="form-text text-muted">
                            <?php echo Text::_('COM_KUNENATOPIC2ARTICLE_SELECT_TOPIC_TO_PROCESS'); ?>
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
                            <?php echo Text::_('COM_KUNENATOPIC2ARTICLE_CATEGORY_ID_FOR_CREATED_ARTICLES'); ?>
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
                            <option value="COM_KUNENATOPIC2ARTICLE_ALL_POSTS" <?php echo ($this->params->post_transfer_scheme ?? '') === 'COM_KUNENATOPIC2ARTICLE_ALL_POSTS' ? 'selected' : ''; ?>>
                                <?php echo Text::_('COM_KUNENATOPIC2ARTICLE_ALL_POSTS'); ?>
                            </option>
                        </select>
                        <small class="form-text text-muted">
                            <?php echo Text::_('COM_KUNENATOPIC2ARTICLE_ENABLE_OR_DISABLE_POST_TRANSFER_SCHEME'); ?>
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
                            <?php echo Text::_('COM_KUNENATOPIC2ARTICLE_MAXIMUM_SIZE_OF_ARTICLE_IN_CHARACTERS'); ?>
                        </small>
                    </div>
                </div>

                <h3><?php echo Text::_('COM_KUNENATOPIC2ARTICLE_POST_INFORMATION'); ?></h3>
                
                <!-- Post Information Section -->
                <div class="row mb-4">
                    <!-- Post Author -->
                    <div class="col-md-6 mb-3">
                        <label for="post_author" class="form-label">
                            <?php echo Text::_('COM_KUNENATOPIC2ARTICLE_POST_AUTHOR'); ?>
                        </label>
                        <select name="jform[post_author]" id="post_author" class="form-select">
                            <option value="COM_KUNENATOPIC2ARTICLE_CURRENT_USER" <?php echo ($this->params->post_author ?? '') === 'COM_KUNENATOPIC2ARTICLE_CURRENT_USER' ? 'selected' : ''; ?>>
                                <?php echo Text::_('COM_KUNENATOPIC2ARTICLE_CURRENT_USER'); ?>
                            </option>
                        </select>
                        <small class="form-text text-muted">
                            <?php echo Text::_('COM_KUNENATOPIC2ARTICLE_INCLUDE_POST_AUTHOR_IN_ARTICLE'); ?>
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
                            <?php echo Text::_('COM_KUNENATOPIC2ARTICLE_DATE_OF_POST_CREATION'); ?>
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
                            <?php echo Text::_('COM_KUNENATOPIC2ARTICLE_TIME_OF_POST_CREATION'); ?>
                        </small>
                    </div>

                    <!-- Post IDs -->
                    <div class="col-md-6 mb-3">
                        <label for="post_ids" class="form-label">
                            <?php echo Text::_('COM_KUNENATOPIC2ARTICLE_POST_IDS'); ?>
                        </label>
                        <select name="jform[post_ids]" id="post_ids" class="form-select">
                            <option value="COM_KUNENATOPIC2ARTICLE_SHOW_IDS" <?php echo ($this->params->post_ids ?? '') === 'COM_KUNENATOPIC2ARTICLE_SHOW_IDS' ? 'selected' : ''; ?>>
                                <?php echo Text::_('COM_KUNENATOPIC2ARTICLE_SHOW_IDS'); ?>
                            </option>
                        </select>
                    </div>
                </div>

                <!-- Дополнительные поля из второго изображения -->
                <div class="row mb-4">
                    <!-- Post Title -->
                    <div class="col-12 mb-3">
                        <label for="post_title" class="form-label">
                            <?php echo Text::_('COM_KUNENATOPIC2ARTICLE_TITLE'); ?>
                        </label>
                        <input type="text" name="jform[post_title]" id="post_title" 
                               class="form-control" value="<?php echo $this->params->post_title ?? ''; ?>">
                    </div>

                    <!-- Topic ID -->
                    <div class="col-md-6 mb-3">
                        <label for="topic_id" class="form-label">
                            <?php echo Text::_('COM_KUNENATOPIC2ARTICLE_FIELD_TOPIC_ID'); ?>
                        </label>
                        <input type="text" name="jform[topic_id]" id="topic_id" 
                               class="form-control" value="<?php echo $this->params->topic_id ?? ''; ?>">
                    </div>

                    <!-- Ignored Authors -->
                    <div class="col-md-6 mb-3">
                        <label for="ignored_authors" class="form-label">
                            <?php echo Text::_('COM_KUNENATOPIC2ARTICLE_FIELD_IGNORED_AUTHORS'); ?>
                        </label>
                        <input type="text" name="jform[ignored_authors]" id="ignored_authors" 
                               class="form-control" value="<?php echo $this->params->ignored_authors ?? ''; ?>">
                    </div>
                </div>

                <!-- Mode Selection -->
                <div class="row mb-4">
                    <div class="col-md-6 mb-3">
                        <label for="mode" class="form-label">
                            <?php echo Text::_('COM_KUNENATOPIC2ARTICLE_FIELD_MODE'); ?>
                        </label>
                        <select name="jform[mode]" id="mode" class="form-select">
                            <option value="COM_KUNENATOPIC2ARTICLE_OPTION_FLAT" <?php echo ($this->params->mode ?? '') === 'COM_KUNENATOPIC2ARTICLE_OPTION_FLAT' ? 'selected' : ''; ?>>
                                <?php echo Text::_('COM_KUNENATOPIC2ARTICLE_OPTION_FLAT'); ?>
                            </option>
                            <option value="tree" <?php echo ($this->params->mode ?? '') === 'tree' ? 'selected' : ''; ?>>
                                <?php echo Text::_('COM_KUNENATOPIC2ARTICLE_OPTION_TREE'); ?>
                            </option>
                        </select>
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
