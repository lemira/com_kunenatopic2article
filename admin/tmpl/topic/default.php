<?php
defined('_JEXEC') or die;

// Необходимые классы
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

// Подключаем JS/CSS для модальных окон Joomla.
HTMLHelper::_('behavior.modal');
HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('bootstrap.framework');

$app = Factory::getApplication();
$input = $app->getInput();
$form = $this->form;
$paramsRemembered = $this->paramsRemembered ?? false;

// PHP-код для формирования URL-адресов.
// лучшее место - ЭТОТ БЛОК ПРЯМО ПЕРЕД ТЕГОМ <form>
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
            
            <a class="btn btn-info modal"
               href="<?php echo $previewUrl; ?>"
               rel="{handler: 'iframe', size: {x: 950, y: 600}, onClose: joomlaModalCloseCallback}">
               <span class="icon-eye" aria-hidden="true"></span>
               <?= Text::_('COM_KUNENATOPIC2ARTICLE_BUTTON_PREVIEW'); ?>
            </a>
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
// Функция, которая будет вызвана при закрытии модального окна
function joomlaModalCloseCallback() {
    // Асинхронно вызываем задачу удаления без перезагрузки страницы
    fetch('<?php echo $deleteUrl; ?>')
        .then(response => response.json())
        .then(data => {
            console.log('Preview deleted:', data);
        })
        .catch(error => console.error('Error deleting preview:', error));
}

// Стандартный обработчик для других кнопок остался без изменений
Joomla.submitbutton = function(task) {
    // Проверяем, если задача не предпросмотр
    if (task === 'article.preview') {
        return; // Ничего не делаем, так как модальное окно обрабатывается автоматически
    }
    
    const form = document.getElementById('adminForm');
    
    if (task === 'save' && !document.formvalidator.isValid(form)) {
        alert('<?= Text::_('JGLOBAL_VALIDATION_FORM_FAILED', true); ?>');
        return false;
    }
    
    Joomla.submitform(task, form);
};
</script>
