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

// ИСПРАВЛЕНИЕ: Декодируем HTML-сущности в URL для правильной работы AJAX
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
        // Используем async/await для более чистого кода
        previewButton.addEventListener('click', async (event) => {
            event.preventDefault();

            try {
                // Показываем индикатор загрузки
                previewButton.disabled = true;
                previewButton.innerHTML = '<span class="icon-spinner icon-spin"></span> <?= Text::_("COM_KUNENATOPIC2ARTICLE_LOADING"); ?>';

                // ШАГ 2: Отправляем POST-запрос на создание статьи
                const response = await fetch('<?= $previewTaskUrl; ?>', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-Token': '<?= Session::getFormToken(); ?>'
                    }
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                // ШАГ 3: Получаем JSON с URL и ID
                const result = await response.json();

                if (result.success && result.data.url) {
                    console.log('Server response:', result);
                    
                    // ДОБАВЛЯЕМ ПАРАМЕТР PREVIEW К URL
                    const previewUrl = result.data.url + (result.data.url.includes('?') ? '&' : '?') + 'preview=1';
                    console.log('Preview URL:', previewUrl);
                    
                    // СОЗДАЕМ ПОЛНОЭКРАННОЕ МОДАЛЬНОЕ ОКНО
                    const modal = document.createElement('div');
                    modal.className = 'modal fade';
                    modal.id = 'previewModal';
                    modal.tabIndex = -1;
                    modal.innerHTML = `
                        <div class="modal-dialog modal-fullscreen">
                            <div class="modal-content">
                                <div class="modal-header bg-light">
                                    <h5 class="modal-title">
                                        <span class="icon-eye"></span>
                                        <?= Text::_('COM_KUNENATOPIC2ARTICLE_PREVIEW_TITLE'); ?>
                                    </h5>
                                    <div class="modal-toolbar">
                                        <button type="button" class="btn btn-sm btn-outline-primary me-2" 
                                                onclick="window.open('${previewUrl}', '_blank')">
                                            <span class="icon-new-tab"></span>
                                            <?= Text::_('COM_KUNENATOPIC2ARTICLE_OPEN_NEW_TAB'); ?>
                                        </button>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                </div>
                                <div class="modal-body p-0">
                                    <iframe src="${previewUrl}" 
                                            width="100%" 
                                            height="100%" 
                                            frameborder="0"
                                            style="border: none;"
                                            onload="document.getElementById('previewButton').disabled = false; document.getElementById('previewButton').innerHTML = '<span class=\'icon-eye\'></span> <?= Text::_('COM_KUNENATOPIC2ARTICLE_BUTTON_PREVIEW'); ?>';">
                                    </iframe>
                                </div>
                                <div class="modal-footer bg-light">
                                    <small class="text-muted">
                                        <?= Text::_('COM_KUNENATOPIC2ARTICLE_PREVIEW_FOOTER'); ?>
                                    </small>
                                    <button type="button" class="btn btn-danger btn-sm" data-bs-dismiss="modal">
                                        <span class="icon-times"></span>
                                        <?= Text::_('JCLOSE'); ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    `;
                    
                    document.body.appendChild(modal);
                    
                    // Показываем модальное окно
                    const bootstrapModal = new bootstrap.Modal(modal);
                    bootstrapModal.show();
                    
                    // Обработчик закрытия модального окна
                    modal.addEventListener('hidden.bs.modal', () => {
                        console.log('Preview modal closed, deleting article...');
                        
                        // При закрытии отправляем запрос на удаление
                        const deleteUrl = '<?= $deleteTaskBaseUrl; ?>' + '&id=' + result.data.id;
                        
                        fetch(deleteUrl, {
                            method: 'POST',
                            headers: { 'X-CSRF-Token': '<?= Session::getFormToken(); ?>' }
                        })
                        .then(res => res.json())
                        .then(delData => {
                            console.log('Delete status:', delData);
                            if (!delData.success) {
                                console.warn('Failed to delete preview article:', delData.message);
                            }
                        })
                        .catch(err => console.error('Delete error:', err))
                        .finally(() => {
                            // Удаляем модальное окно из DOM
                            document.body.removeChild(modal);
                        });
                    });
                    
                } else {
                    // Показываем сообщение об ошибке
                    alert('Error creating preview: ' + (result.message || 'Unknown error'));
                    previewButton.disabled = false;
                    previewButton.innerHTML = '<span class="icon-eye"></span> <?= Text::_('COM_KUNENATOPIC2ARTICLE_BUTTON_PREVIEW'); ?>';
                }

            } catch (error) {
                console.error('Preview request failed:', error);
                alert('Preview request failed: ' + error.message);
                previewButton.disabled = false;
                previewButton.innerHTML = '<span class="icon-eye"></span> <?= Text::_('COM_KUNENATOPIC2ARTICLE_BUTTON_PREVIEW'); ?>';
            }
        });
    }
    
    // Стандартный обработчик для других кнопок
    Joomla.submitbutton = function(task) {
        Joomla.submitform(task, document.getElementById('adminForm'));
    }
});
</script>
