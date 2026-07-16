<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_kunenatopic2article
 * @copyright   (C) 2025 Leonid Ratner. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;

HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('bootstrap.framework');

// Передаем абсолютно всё через опции скрипта
Factory::getApplication()->getDocument()->addScriptOptions('kunena_preview_data', [
    'previewUrl' => Route::_('index.php?option=com_kunenatopic2article&task=article.preview&format=json', false),
    'deleteUrl'  => Route::_('index.php?option=com_kunenatopic2article&task=article.deletePreview&format=json', false),
    'token'      => Session::getFormToken(),
    'msgAuth'    => Text::_('COM_KUNENATOPIC2ARTICLE_PREVIEW_AUTH_REQUIRED')
]);

$form = $this->form;
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
            
            <button type="button" id="previewButton" class="btn btn-info me-2" <?= $this->canCreate ? '' : 'disabled'; ?>>
                <span class="icon-eye" aria-hidden="true"></span>
                <?= Text::_('COM_KUNENATOPIC2ARTICLE_BUTTON_PREVIEW'); ?>
            </button>
            
            <button type="button" id="btn_create" class="btn btn-success me-2" onclick="Joomla.submitbutton('article.create')" <?= $this->canCreate ? '' : 'disabled'; ?>>
                <?= Text::_('COM_KUNENATOPIC2ARTICLE_BUTTON_CREATE'); ?>
            </button>
        </div>

        <?php if ($form): ?>
            <?= $form->renderFieldset('article_params'); ?>
            <?= $form->renderFieldset('post_info'); ?>
        <?php endif; ?>

        <input type="hidden" name="task" value="" />
        <?= HTMLHelper::_('form.token'); ?>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const jOptions = Joomla.getOptions('kunena_preview_data');
    const previewButton = document.getElementById('previewButton');
    const postInfoStyleFields = [
        'post_info_layout',
        'post_info_background',
        'post_info_text_color',
        'post_info_font_size',
        'post_info_ids_font_size',
        'post_info_align',
        'post_info_width',
        'post_info_accent_color'
    ];

    const findFieldRow = (fieldName) => {
        const field = document.querySelector(`[name="jform[${fieldName}]"]`);

        return field
            ? field.closest('.control-group, .form-group, .mb-3, .control')
            : null;
    };

    const togglePostInfoStyleFields = () => {
        const enabled = document.querySelector('input[name="jform[post_info_style_enabled]"]:checked')?.value === '1';

        postInfoStyleFields.forEach((fieldName) => {
            const row = findFieldRow(fieldName);

            if (row) {
                row.style.display = enabled ? '' : 'none';
            }
        });
    };

    document
        .querySelectorAll('input[name="jform[post_info_style_enabled]"]')
        .forEach((input) => input.addEventListener('change', togglePostInfoStyleFields));

    togglePostInfoStyleFields();

    const showPreviewError = (message) => {
        if (Joomla.renderMessages) {
            Joomla.renderMessages({ error: [message] });
        } else {
            alert(message);
        }
    };

    const postWithToken = async (url) => {
        const body = new URLSearchParams();
        body.append(jOptions.token, '1');

        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                'X-CSRF-Token': jOptions.token
            },
            body
        });

        const rawText = await response.text();
        let result;

        try {
            result = JSON.parse(rawText);
        } catch (error) {
            throw new Error(rawText || response.statusText || 'Invalid preview response');
        }

        if (!response.ok || result.success === false) {
            throw new Error(result.message || response.statusText || 'Preview request failed');
        }

        return result.data || result;
    };

    if (previewButton && jOptions) {
        previewButton.addEventListener('click', async (event) => {
            event.preventDefault();

            try {
                // 1. Создание превью
                const previewData = await postWithToken(jOptions.previewUrl);

                if (previewData.url) {
                    // Сразу формируем URL удаления, он нам понадобится в любом случае
                    const deleteUrl = jOptions.deleteUrl + '&id=' + previewData.id;

                    // 2. Пытаемся получить контент статьи
                    const articleResponse = await fetch(previewData.url);

                 if (articleResponse.status === 404) {
    // Чистим базу (сравнить с 3.?)
    postWithToken(deleteUrl);

    // Показываем расширенное модальное окно
    const errorModal = document.createElement('div');
    errorModal.className = 'modal fade';
    errorModal.innerHTML = `
        <div class="modal-dialog modal-lg"> 
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title text-white">
                        <span class="icon-info" aria-hidden="true"></span> Preview Info
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" style="padding: 2.5rem; font-size: 1.15rem; line-height: 1.6;">
                    <p class="mb-0">${jOptions.msgAuth}</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">ОК</button>
                </div>
            </div>
        </div>`;
    
    document.body.appendChild(errorModal);
    const bsErrorModal = new bootstrap.Modal(errorModal);
    bsErrorModal.show();

    // Удаляем из DOM после закрытия, чтобы не плодить элементы
    errorModal.addEventListener('hidden.bs.modal', () => {
        document.body.removeChild(errorModal);
    });

    return;
}

                    const previewComponentUrl = previewData.url + (previewData.url.includes('?') ? '&' : '?') + 'tmpl=component';

                    // 3. Показываем компонентную область превью в iframe, чтобы layout сайта не ломал модальное окно
                    const modalDiv = document.createElement('div');
                    modalDiv.className = 'modal fade';
                    modalDiv.innerHTML = `
                        <div class="modal-dialog modal-lg" style="max-width: 80%;">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Preview</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body" style="height: 80vh; overflow: hidden; padding: 0;">
                                    <iframe src="${previewComponentUrl}" style="width: 100%; height: 100%; border: 0; display: block;"></iframe>
                                </div>
                            </div>
                        </div>`;
                    
                    document.body.appendChild(modalDiv);
                    const bootstrapModal = new bootstrap.Modal(modalDiv);
                    bootstrapModal.show();

                    modalDiv.addEventListener('hidden.bs.modal', () => {
                        postWithToken(deleteUrl);
                        document.body.removeChild(modalDiv);
                    });
                }
            } catch (error) {
                console.error('Preview error:', error);
                showPreviewError(error.message || 'Preview error');
            }
        });
    }

    Joomla.submitbutton = function(task) {
        Joomla.submitform(task, document.getElementById('adminForm'));
    }
});
</script>
