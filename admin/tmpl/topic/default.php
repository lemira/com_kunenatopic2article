<?php
defined('_JEXEC') or die;

// Необходимые классы
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

// Больше не нужен HTMLHelper::_('behavior.modal');
// Оставляем только нужные для формы хелперы
HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('bootstrap.framework');

$app = Factory::getApplication();
$form = $this->form;

// PHP-код для формирования URL-адресов. Он остается без изменений.
$previewUrl = Route::_('index.php?option=com_kunenatopic2article&task=article.preview&id=' . $this->item->id . '&' . Session::getFormToken() . '=1&tmpl=component');
$deleteUrl = Route::_('index.php?option=com_kunenatopic2article&task=article.deletePreview&' . Session::getFormToken() . '=1');
?>

<form action="<?= Route::_('index.php?option=com_kunenatopic2article'); ?>" method="post" name="adminForm" id="adminForm" class="form-validate">
    <div class="container-fluid">
        <h1><?= Text::_('COM_KUNENATOPIC2ARTICLE_PARAMS_TITLE'); ?></h1>
        <div class="btn-toolbar mb-3">
            <button type="button" class="btn btn-primary me-2" onclick="Joomla.submitbutton('save')">
                <?= Text::_('COM_KUNENATOPIC2ARTICLE_BUTTON_REMEMBER'); ?>
            </button>
            <button type="button" class="btn btn-secondary me-2" onclick="Joomla.submitbutton('reset')">
                <?= Text::_('COM_KUNENATOPIC2ARTICLE_BUTTON_RESET'); ?>
            </button>
            <button type="button" id="btn_create" class="btn btn-success" onclick="Joomla.submitbutton('article.create')" <?= $this->canCreate ? '' : 'disabled'; ?>>
                <?= Text::_('COM_KUNENATOPIC2ARTICLE_BUTTON_CREATE'); ?>
            </button>
            
            <a id="previewButton" class="btn btn-info" href="#">
               <span class="icon-eye" aria-hidden="true"></span>
               <?= Text::_('COM_KUNENATOPIC2ARTICLE_BUTTON_PREVIEW'); ?>
            </a>
        </div>

        <h3><?= Text::_('COM_KUNENATOPIC2ARTICLE_ARTICLE_PARAMS'); ?></h3>
        <?php if ($form): ?>
            <?= $form->renderFieldset('article_params'); ?>
        <?php endif; ?>

        <h3><?= Text::_('COM_KUNENATOPIC2ARTICLE_POST_INFO'); ?></h3>
        <?php if ($form): ?>
            <?= $form->renderFieldset('post_info'); ?>
        <?php endif; ?>

        <input type="hidden" name="task" value="" />
        <?= HTMLHelper::_('form.token'); ?>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const previewButton = document.getElementById('previewButton');

    if (previewButton) {
        previewButton.addEventListener('click', (event) => {
            event.preventDefault();

            Joomla.Modal.iframe(
                '<?php echo $previewUrl; ?>', // URL для содержимого модального окна
                { // Опции
                    title: '<?= Text::_('COM_KUNENATOPIC2ARTICLE_PREVIEW_TITLE', true); ?>',
                    width: 950,
                    height: 600,
                    onClose: () => {
                        // Эта функция выполнится при закрытии окна
                        fetch('<?php echo $deleteUrl; ?>')
                            .then(response => response.json())
                            .then(data => {
                                console.log('Preview deleted:', data);
                            })
                            .catch(error => console.error('Error deleting preview:', error));
                    }
                }
            );
        });
    }

    // Стандартный обработчик для других кнопок
    if (typeof Joomla.submitbutton === 'undefined') {
        Joomla.submitbutton = function(task) {
            Joomla.submitform(task, document.getElementById('adminForm'));
        }
    }
});
</script>
