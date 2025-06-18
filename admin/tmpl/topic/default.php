<?php
/**
 * @package     KunenaTopic2Article
 * @subpackage  Administrator
 */
\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;

HTMLHelper::_('behavior.formvalidator');

$form = $this->form;
$paramsRemembered = $this->paramsRemembered ?? false; // Состояние кнопки Create Articles
?>

<form action="<?= Route::_('index.php?option=com_kunenatopic2article&task=save'); ?>" method="post" name="adminForm" id="adminForm" class="form-validate">
 <!-- МИГАЕТ! <input type="hidden" name="task" value="" />  позволяет Joomla.submitbutton() динамически устанавливать значение задачи -->
    <div class="container-fluid">
        <h1><?= Text::_('COM_KUNENATOPIC2ARTICLE_PARAMS_TITLE'); ?></h1>
        <div class="btn-toolbar mb-3">
            <button type="button" class="btn btn-primary me-2" onclick="Joomla.submitbutton('save')">
                <?= Text::_('COM_KUNENATOPIC2ARTICLE_BUTTON_REMEMBER'); ?>
            </button>
            <button type="button" class="btn btn-secondary me-2" onclick="Joomla.submitbutton('reset')">
                <?= Text::_('COM_KUNENATOPIC2ARTICLE_BUTTON_RESET'); ?>
            </button>
            <button type="button" id="btn_create" class="btn btn-success" onclick="Joomla.submitbutton('create')" <?= $paramsRemembered ? '' : 'disabled'; ?>>
                <?= Text::_('COM_KUNENATOPIC2ARTICLE_BUTTON_CREATE'); ?>
            </button>
            </button>
            <button type="button" id="btn_create" class="btn btn-success" onclick="Joomla.submitbutton('display.create')" <?= $paramsRemembered ? '' : 'disabled'; ?>>
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
    Joomla.submitbutton = function(task) {
        const form = document.getElementById('adminForm');
        if (task === 'save' && form.classList.contains('form-validate')) {
            // Используем стандартную HTML5-валидацию
            if (form.reportValidity()) {
                Joomla.submitform(task, form);
            } else {
                alert('<?= Text::_('JGLOBAL_VALIDATION_FORM_FAILED'); ?>');
            }
        } else {
            Joomla.submitform(task, form);
        }
    };
</script>
