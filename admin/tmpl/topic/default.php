<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

HTMLHelper::_('behavior.formvalidator');

$app = Factory::getApplication();
$input = $app->getInput(); // Joomla 5
$form = $this->form;
$paramsRemembered = $this->paramsRemembered ?? false;
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
            <button type="button" id="btn_preview" class="btn btn-info" onclick="Joomla.submitbutton('article.create')"> 
                   <?= Text::_('COM_KUNENATOPIC2ARTICLE_BUTTON_PREVIEW'); ?>
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

<?php if ($input->getBool('preview_closed')) : ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    fetch('<?= Route::_("index.php?option=com_kunenatopic2article&task=article.deletePreviewArticle&".Session::getFormToken()."=1") ?>');
});
</script>
<?php endif; ?>

<script>
Joomla.submitbutton = function(task) {
    const form = document.getElementById('adminForm');
    
    // Обработка preview
    if (task === 'article.create') {
        const isPreview = event?.target?.id === 'btn_preview';
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'is_preview';
        input.value = isPreview ? '1' : '0';
        form.appendChild(input);

        // Валидация для preview
        if (isPreview && form.classList.contains('form-validate')) {
            if (!form.reportValidity()) {
                alert('<?= Text::_('JGLOBAL_VALIDATION_FORM_FAILED'); ?>');
                return false;
            }
        }

        if (isPreview) {
            // AJAX-запрос для preview
            fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // Открываем модальное окно
                    const modal = new bootstrap.Modal(document.createElement('div'));
                    modal._element.innerHTML = `
                        <div class="modal-dialog modal-xl">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title"><?= Text::_('COM_KUNENATOPIC2ARTICLE_PREVIEW') ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <iframe src="${data.preview_url}" style="width:100%; height:80vh; border:none;"></iframe>
                                </div>
                            </div>
                        </div>
                    `;
                    modal.show();
                    
                    // Удаление статьи при закрытии
                    modal._element.addEventListener('hidden.bs.modal', function() {
                        fetch('<?= Route::_("index.php?option=com_kunenatopic2article&task=article.deletePreviewArticle&".JSession::getFormToken()."=1") ?>');
                    });
                }
            });
            return;
        }
    }
    
    // Стандартная валидация для сохранения
    if (task === 'save' && form.classList.contains('form-validate')) {
        if (!form.reportValidity()) {
            alert('<?= Text::_('JGLOBAL_VALIDATION_FORM_FAILED'); ?>');
            return false;
        }
    }
    
    // Отправка формы для не-preview действий
    Joomla.submitform(task, form);
};
</script>
