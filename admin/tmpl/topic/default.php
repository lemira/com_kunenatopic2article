<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_kunenatopic2article
 *
 * @copyright   (C) 2025 Leonid Ratner. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

// Необходимые классы
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('bootstrap.framework');

$app = Factory::getApplication();
$form = $this->form;

// Декодируем HTML-сущности в URL для правильной работы AJAX
$previewTaskUrl = html_entity_decode(
    Route::_('index.php?option=com_kunenatopic2article&task=article.preview&format=json'),
    ENT_QUOTES,
    'UTF-8'
);
$deleteTaskBaseUrl = html_entity_decode(
    Route::_('index.php?option=com_kunenatopic2article&task=article.deletePreview&format=json'),
    ENT_QUOTES,
    'UTF-8'
);
?>

<form action="<?= Route::_('index.php?option=com_kunenatopic2article'); ?>" method="post" name="adminForm" id="adminForm" class="form-validate">
    <div class="container-fluid">
        <h1><?= Text::_('COM_KUNENATOPIC2ARTICLE_PARAMS_TITLE'); ?></h1>
        <div class="btn-toolbar mb-3">
        <!-- Remember и Reset Parameters всегда активны -->
        <button type="button" class="btn btn-primary me-2" onclick="Joomla.submitbutton('save')">
                <?= Text::_('COM_KUNENATOPIC2ARTICLE_BUTTON_REMEMBER'); ?>
            </button>
            <button type="button" class="btn btn-secondary me-2" onclick="Joomla.submitbutton('reset')">
                <?= Text::_('COM_KUNENATOPIC2ARTICLE_BUTTON_RESET'); ?>
            </button>
            <!-- Create Articles и Preview синхронизированы через can_create -->
             <button type="button" id="previewButton" class="btn btn-info me-2" <?= $this->canCreate ? '' : 'disabled'; ?>>
                <span class="icon-eye" aria-hidden="true"></span>
                <?= Text::_('COM_KUNENATOPIC2ARTICLE_BUTTON_PREVIEW'); ?>
            </button>
            <button type="button" id="btn_create" class="btn btn-success me-2"  onclick="Joomla.submitbutton('article.create')" <?= $this->canCreate ? '' : 'disabled'; ?>>
                <?= Text::_('COM_KUNENATOPIC2ARTICLE_BUTTON_CREATE'); ?>
            </button>
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
        previewButton.addEventListener('click', async (event) => {
            event.preventDefault();

            try {
                // 1. Создаем preview-статью в БД (контроллер возвращает ID и URL)
                const response = await fetch('<?= $previewTaskUrl; ?>', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-Token': '<?= Session::getFormToken(); ?>'
                    }
                });

                const result = await response.json();

                if (result.success && result.data.url) {
                    // 2. Пытаемся загрузить HTML статьи
                    const articleResponse = await fetch(result.data.url);

                    // ПРОВЕРКА НА 404 (Если юзер не авторизован на фронтенде)
                    if (articleResponse.status === 404) {
                        Joomla.removeMessages();
                        Joomla.renderMessages({
                            'warning': ['<?= Text::_('COM_KUNENATOPIC2ARTICLE_PREVIEW_AUTH_REQUIRED'); ?>']
                        });
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                        return;
                    }

                    let articleHtml = await articleResponse.text();

                    // 3. СРАЗУ удаляем статью из БД (чистим за собой)
                    const deleteUrl = '<?= $deleteTaskBaseUrl; ?>' + '&id=' + result.data.id;
                    fetch(deleteUrl, {
                        method: 'POST',
                        headers: { 'X-CSRF-Token': '<?= Session::getFormToken(); ?>' }
                    }).catch(err => console.error('Delete error:', err));

                    // 4. Показываем модальное окно с контентом
                    const modal = document.createElement('div');
                    modal.className = 'modal fade';
                    modal.innerHTML = `
                        <div class="modal-dialog" style="max-width: 70%; margin: 2% auto;">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Preview</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body" style="max-height: 80vh; overflow-y: auto;">
                                    ${articleHtml}
                                </div>
                            </div>
                        </div>
                    `;
                    
                    document.body.appendChild(modal);
                    const bootstrapModal = new bootstrap.Modal(modal);
                    bootstrapModal.show();

                    modal.addEventListener('hidden.bs.modal', () => {
                        document.body.removeChild(modal);
                    });

                } else {
                    alert('Error: ' + (result.message || 'Unknown error'));
                }

            } catch (error) {
                console.error('Preview failed:', error);
            }
        });
    }

    // Обработка стандартных кнопок Joomla (Save/Reset)
    Joomla.submitbutton = function(task) {
        Joomla.submitform(task, document.getElementById('adminForm'));
    }
});
</script>
