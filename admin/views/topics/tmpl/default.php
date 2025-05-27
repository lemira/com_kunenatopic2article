<?php
\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;

// Получение приложения и флага для активации кнопки "Create Articles"
$app = Factory::getApplication();
$canCreate = $app->getUserState('com_kunenatopic2article.can_create', false);

// Подключение необходимых скриптов (если не через шаблон)
HTMLHelper::_('bootstrap.framework');
HTMLHelper::_('behavior.keepalive');
?>

<form action="<?php echo Route::_('index.php?option=com_kunenatopic2article'); ?>" method="post" name="adminForm" id="adminForm">
    <div class="container mt-4">
        <h2><?php echo Text::_('COM_KUNENATOPIC2ARTICLE_TITLE'); ?></h2>

        <!-- Поле: ID темы -->
        <div class="mb-3">
            <label for="topic_id" class="form-label"><?php echo Text::_('COM_KUNENATOPIC2ARTICLE_FIELD_TOPIC_ID'); ?></label>
            <input type="text" name="jform[topic_id]" id="topic_id" class="form-control" value="<?php echo $this->params->topic_id ?? ''; ?>">
        </div>

        <!-- Поле: Игнорируемые авторы -->
        <div class="mb-3">
            <label for="ignored_authors" class="form-label"><?php echo Text::_('COM_KUNENATOPIC2ARTICLE_FIELD_IGNORED_AUTHORS'); ?></label>
            <input type="text" name="jform[ignored_authors]" id="ignored_authors" class="form-control" value="<?php echo $this->params->ignored_authors ?? ''; ?>">
        </div>

        <!-- Поле: Режим переноса -->
        <div class="mb-3">
            <label for="mode" class="form-label"><?php echo Text::_('COM_KUNENATOPIC2ARTICLE_FIELD_MODE'); ?></label>
            <select name="jform[mode]" id="mode" class="form-select">
                <option value="flat" <?php echo ($this->params->mode ?? '') === 'flat' ? 'selected' : ''; ?>>
                    <?php echo Text::_('COM_KUNENATOPIC2ARTICLE_OPTION_FLAT'); ?>
                </option>
                <option value="tree" <?php echo ($this->params->mode ?? '') === 'tree' ? 'selected' : ''; ?>>
                    <?php echo Text::_('COM_KUNENATOPIC2ARTICLE_OPTION_TREE'); ?>
                </option>
            </select>
        </div>

        <!-- Кнопки управления -->
        <div class="mt-4">
            <!-- Кнопка сохранить параметры -->
            <button type="submit" name="task" value="article.save" class="btn btn-primary">
                <?php echo Text::_('COM_KUNENATOPIC2ARTICLE_BUTTON_REMEMBER'); ?>
            </button>

            <!-- Кнопка сброса -->
            <button type="submit" name="task" value="article.reset" class="btn btn-warning ms-2">
                <?php echo Text::_('COM_KUNENATOPIC2ARTICLE_BUTTON_RESET'); ?>
            </button>

            <!-- Кнопка создания статей -->
            <button type="submit" name="task" value="article.create" class="btn btn-success ms-2"
                <?php echo !$canCreate ? 'disabled' : ''; ?>>
                <?php echo Text::_('COM_KUNENATOPIC2ARTICLE_BUTTON_CREATE'); ?>
            </button>
        </div>

        <!-- Скрытые поля -->
        <input type="hidden" name="option" value="com_kunenatopic2article">
        <input type="hidden" name="controller" value="article">
        <?php echo HTMLHelper::_('form.token'); ?>
    </div>
</form>
