<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('bootstrap.framework'); // Подключает Bootstrap 5
HTMLHelper::_('behavior.core'); // Подключает Joomla.request и другие утилиты

$app = Factory::getApplication();
$input = $app->getInput();
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
document.addEventListener('DOMContentLoaded', async function() {
    try {
        const token = '<?= Session::getFormToken() ?>';
        const response = await fetch('<?= Route::_("index.php?option=com_kunenatopic2article&task=article.deletePreviewArticle&format=json") ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-CSRF-Token': token
            },
            body: new URLSearchParams({
                [token]: '1'
            })
        });

        const result = await response.json();
        if (!result.success) {
            Joomla.renderMessages({ 'error': [result.message || 'Error deleting preview article'] });
        }
    } catch (error) {
        Joomla.renderMessages({ 'error': [error.message] });
    }
});
</script>
<?php endif; ?>

<script>
Joomla.submitbutton = function(task) {
    const form = document.getElementById('adminForm');
    if (task === 'save' && form.classList.contains('form-validate')) {
        if (form.reportValidity()) {
            Joomla.submitform(task, form);
        } else {
            alert('<?= Text::_('JGLOBAL_VALIDATION_FORM_FAILED'); ?>');
        }
    } else {
        Joomla.submitform(task, form);
    }
};

document.addEventListener('DOMContentLoaded', function() {
    const previewButton = document.getElementById('btn_preview');
    if (!previewButton) {
        return;
    }

    previewButton.addEventListener('click', async function(event) {
        event.preventDefault();

        const form = document.getElementById('adminForm');
        const formData = new FormData(form);
        const token = '<?= Session::getFormToken() ?>';

        formData.append('is_preview', '1');
        formData.append(token, '1');

        try {
            const response = await fetch('<?= Route::_("index.php?option=com_kunenatopic2article&task=article.preview&format=json") ?>', {
                method: 'POST',
                headers: {
                    'X-CSRF-Token': token
                },
                body: formData
            });

            if (!response.ok) {
                throw new Error(response.statusText);
            }

            const result = await response.json();

            if (result.success) {
                const iframe = Joomla.Modal.createIframe({
                    src: result.data.url,
                    title: '<?= Text::_('COM_KUNENATOPIC2ARTICLE_PREVIEW_TITLE', true); ?>',
                    width: '80%',
                    height: '80%'
                });
                
                const modal = Joomla.Bootstrap.Modal.open(iframe, {});
                
                modal.addEventListener('hidden.bs.modal', async () => {
                    const deleteData = new FormData();
                    deleteData.append('id', result.data.id);
                    deleteData.append(token, '1');

                    try {
                        const deleteResponse = await fetch('<?= Route::_("index.php?option=com_kunenatopic2article&task=article.deletePreview&format=json") ?>', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-Token': token
                            },
                            body: deleteData
                        });

                        const deleteResult = await deleteResponse.json();
                        if (!deleteResult.success) {
                            Joomla.renderMessages({ 'error': [deleteResult.message || 'Error deleting preview article'] });
                        }
                    } catch (error) {
                        Joomla.renderMessages({ 'error': [error.message] });
                    }
                    
                    modal.remove();
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
