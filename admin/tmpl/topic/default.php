<?php
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
        previewButton.addEventListener('click', async (event) => {
            event.preventDefault();

            try {
                // 1. Создаем preview-статью
                const response = await fetch('<?= $previewTaskUrl; ?>', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-Token': '<?= Session::getFormToken(); ?>'
                    }
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const result = await response.json();

                if (result.success && result.data.url) {
                   // 2. Загружаем HTML статьи и удаляем стрелки
                        const articleResponse = await fetch(result.data.url);
                        let articleHtml = await articleResponse.text();

                    // Убираем иконки внешних ссылок (сохраняем все стили)
                    articleHtml = articleHtml.replace(/<span class="[^"]*icon-external-link[^"]*"[^>]*><\/span>/g, '');
                    articleHtml = articleHtml.replace(/<i class="[^"]*icon-external-link[^"]*"[^>]*><\/i>/g, '');
                    articleHtml = articleHtml.replace(/<svg[^>]*external-link[^>]*>.*?<\/svg>/gs, '');

                    
                    // 3. СРАЗУ удаляем статью из БД
                    const deleteUrl = '<?= $deleteTaskBaseUrl; ?>' + '&id=' + result.data.id;
                    fetch(deleteUrl, {
                        method: 'POST',
                        headers: { 'X-CSRF-Token': '<?= Session::getFormToken(); ?>' }
                    }).catch(err => console.error('Delete error:', err));

               // 4. Создаем модальное окно с HTML-копией (70% ширины), сохраняем все оригинальные стили
const modal = document.createElement('div');
modal.className = 'modal fade';
modal.innerHTML = `
    <div class="modal-dialog" style="max-width: 70%; margin: 2% auto;">
        <div class="modal-content">
            <div class="modal-header">
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

                        // ДОБАВЛЯЕМ, Т.К. СТРЕЛКИ ОСТАЛИСЬ
const antiArrowStyle = document.createElement('style');
antiArrowStyle.textContent = `
    .icon-external-link::after,
    [class*="external"]::after,
    a[target="_blank"]::after {
        display: none !important;
        content: none !important;
    }
`;
document.head.appendChild(antiArrowStyle);

// Удаляем стиль при закрытии
modal.addEventListener('hidden.bs.modal', () => {
    document.head.removeChild(antiArrowStyle);
    document.body.removeChild(modal);
});
              
                        // 5. При закрытии удаляем модальное окно
                    modal.addEventListener('hidden.bs.modal', () => {
                        document.body.removeChild(modal);
                    });
                    
                } else {
                    alert('Error creating preview: ' + (result.message || 'Unknown error'));
                }

            } catch (error) {
                console.error('Preview failed:', error);
                alert('Preview request failed: ' + error.message);
            }
        });
    }
    
    Joomla.submitbutton = function(task) {
        Joomla.submitform(task, document.getElementById('adminForm'));
    }
});
</script>
