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
            <button type="button" id="btn_preview" class="btn btn-info">
             <span class="icon-eye" aria-hidden="true"></span>
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

   // Обр превью дж 
   document.addEventListener('DOMContentLoaded', function() {
    const previewButton = document.getElementById('btn_preview');
    if (!previewButton) {
        return;
    }

    previewButton.addEventListener('click', async function(event) {
        event.preventDefault();

        const form = document.getElementById('adminForm');
        const formData = new FormData(form);

        try {
            // Отправляем AJAX-запрос с помощью Joomla.fetch
            const response = await Joomla.fetch('index.php?option=com_kunenatopic2article&task=article.preview&format=json', {
                method: 'POST',
                body: formData,
            });

            if (!response.ok) {
                throw new Error(response.statusText);
            }

            const result = await response.json();

            if (result.success) {
                // Создаем iframe для модального окна
                const iframe = Joomla.Modal.createIframe({
                    src: result.data.url,
                    title: '<?= Text::_('COM_KUNENATOPIC2ARTICLE_PREVIEW_TITLE', true); ?>',
                    width: '80%',
                    height: '80%'
                });
                
                // Открываем модальное окно Bootstrap
                const modal = Joomla.Bootstrap.Modal.open(iframe, {
                    // Опции модального окна, если нужны
                });
                
                // Обработчик закрытия модального окна для удаления статьи
                modal.addEventListener('hidden.bs.modal', async () => {
                    const deleteData = new FormData();
                    deleteData.append('id', result.data.id);
                    
                    await Joomla.fetch('index.php?option=com_kunenatopic2article&task=article.deletePreview&format=json', {
                        method: 'POST',
                        body: deleteData,
                    });
                    
                    modal.remove(); // Удаляем элемент модального окна из DOM
                });

            } else {
                Joomla.renderMessages({ 'error': [result.message] });
            }
        } catch (error) {
            Joomla.renderMessages({ 'error': [error.message] });
        }
    });
});
   
</script>
