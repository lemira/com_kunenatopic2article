<?php
\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('formbehavior.chosen', 'select');

// Извлечение данных из $displayData
$form = $this->form;        // вместо $form = $this->get('form');
$paramsRemembered = $this->paramsRemembered ?? false;
?>

<form action="<?= Route::_('index.php?option=com_kunenatopic2article&task=topic.save'); ?>" method="post" name="adminForm" id="adminForm" class="form-validate">
    <div class="container-fluid">
        <h1><?= Text::_('COM_KUNENATOPIC2ARTICLE_PARAMS_TITLE'); ?></h1>
        <div class="btn-toolbar mb-3">
            <button type="button" class="btn btn-primary me-2" onclick="Joomla.submitbutton('save')">
                <?= Text::_('COM_KUNENATOPIC2ARTICLE_BUTTON_REMEMBER'); ?>
            </button>
            <button type="button" class="btn btn-secondary me-2" onclick="Joomla.submitbutton('reset')">
                <?= Text::_('COM_KUNENATOPIC2ARTICLE_BUTTON_RESET'); ?>
            </button>
            <button type="button" class="btn btn-success" id="createArticlesBtn" onclick="Joomla.submitbutton('create')" <?= $paramsRemembered ? '' : 'disabled' ?>>
                <?= Text::_('COM_KUNENATOPIC2ARTICLE_BUTTON_CREATE'); ?>
            </button>
        </div>

        <h3><?= Text::_('COM_KUNENATOPIC2ARTICLE_ARTICLE_PARAMS'); ?></h3>
        <?php if ($form): ?>
            <?= $form->renderFieldset('article_params'); ?>
        <?php else: ?>
            <div class="alert alert-danger"><?= Text::_('COM_KUNENATOPIC2ARTICLE_FORM_IS_EMPTY'); ?></div>
        <?php endif; ?>

        <h3><?= Text::_('COM_KUNENATOPIC2ARTICLE_POST_INFO'); ?></h3>
        <?php if ($form): ?>
            <?= $form->renderFieldset('post_info'); ?>
        <?php else: ?>
            <div class="alert alert-danger"><?= Text::_('COM_KUNENATOPIC2ARTICLE_FORM_IS_EMPTY'); ?></div>
        <?php endif; ?>

        <input type="hidden" name="task" value="" />
        <?= HTMLHelper::_('form.token'); ?>
    </div>
</form>

<script>
    // Функция для управления состоянием кнопки Create Articles
    function toggleCreateButton(enabled) {
        const createBtn = document.getElementById('createArticlesBtn');
        if (createBtn) {
            if (enabled) {
                createBtn.removeAttribute('disabled');
                createBtn.classList.remove('btn-outline-success');
                createBtn.classList.add('btn-success');
            } else {
                createBtn.setAttribute('disabled', 'disabled');
                createBtn.classList.remove('btn-success');
                createBtn.classList.add('btn-outline-success');
            }
        }
    }

    // Инициализация состояния кнопки при загрузке страницы
    document.addEventListener('DOMContentLoaded', function() {
        const paramsRemembered = <?= json_encode($paramsRemembered) ?>;
        toggleCreateButton(paramsRemembered);
    });

    Joomla.submitbutton = function(task) {
        const form = document.getElementById('adminForm');
        
        if (task === 'create') {
            // Проверяем, что кнопка активна
            const createBtn = document.getElementById('createArticlesBtn');
            if (createBtn && createBtn.hasAttribute('disabled')) {
                alert('<?= Text::_('COM_KUNENATOPIC2ARTICLE_PLEASE_REMEMBER_PARAMS_FIRST'); ?>');
                return;
            }
        }
        
        if (form && form.classList.contains('form-validate')) {
            if (window.Joomla && Joomla.isValid(form)) {
                Joomla.submitform(task, form);
            } else {
                alert('<?= Text::_('JGLOBAL_VALIDATION_FORM_FAILED'); ?>');
            }
        }
    };
</script>
