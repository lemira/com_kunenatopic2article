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
                // ШАГ 2: Отправляем POST-запрос на создание статьи (как в оригинале)
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
                    console.log('Preview URL:', result.data.url);
                    
                          // Проверяем, доступен ли Joomla.Modal
                    if (typeof Joomla !== 'undefined' && Joomla.Modal) {
                        // Используем встроенный Joomla Modal
                        Joomla.Modal.show({
                            title: 'Preview Article',
                            body: `<iframe src="${result.data.url}" width="100%" height="600px" style="border: none;"></iframe>`,
                            width: '90%',
                            height: '80%',
                            onClose: () => {
                                console.log('Joomla modal closed, deleting article...');
                                deletePreviewArticle(result.data.id);
                            }
                        });
                    } else {
                        // Fallback: создаем простое модальное окно без Bootstrap
                        const modalOverlay = document.createElement('div');
                        modalOverlay.style.cssText = `
                            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
                            background: rgba(0,0,0,0.5); z-index: 9999; display: flex;
                            align-items: center; justify-content: center;
                        `;
                        
                        const modalContent = document.createElement('div');
                        modalContent.style.cssText = `
                            background: white; width: 90%; height: 80%; border-radius: 8px;
                            position: relative; box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                        `;
                        
                        modalContent.innerHTML = `
                            <div style="padding: 15px; border-bottom: 1px solid #ddd; display: flex; justify-content: space-between; align-items: center;">
                                <h5 style="margin: 0;">Preview Article</h5>
                                <button id="closeModal" style="background: none; border: none; font-size: 24px; cursor: pointer;">&times;</button>
                            </div>
                            <iframe src="${result.data.url}" width="100%" height="calc(100% - 60px)" style="border: none;"></iframe>
                        `;
                        
                        modalOverlay.appendChild(modalContent);
                        document.body.appendChild(modalOverlay);
                        
                        // Обработчик закрытия
                        const closeModal = () => {
                            console.log('Custom modal closed, deleting article...');
                            document.body.removeChild(modalOverlay);
                            deletePreviewArticle(result.data.id);
                        };
                        
                        document.getElementById('closeModal').onclick = closeModal;
                        modalOverlay.onclick = (e) => {
                            if (e.target === modalOverlay) closeModal();
                        };
                    }
                    
                } else {
                    // Показываем сообщение об ошибке, если сервер вернул success: false
                    alert('Error creating preview: ' + (result.message || 'Unknown error'));
                }

            } catch (error) {
                console.error('Preview request failed:', error);
                alert('Preview request failed: ' + error.message);
            }
        });
    }
    
      // Стандартный обработчик для других кнопок
            Joomla.submitbutton = function(task) {
            Joomla.submitform(task, document.getElementById('adminForm'));
      }
});
</script>
