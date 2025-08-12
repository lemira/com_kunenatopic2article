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
                    
                    // Временно используем обычное окно вместо модального для отладки
                    const previewWindow = window.open(result.data.url, 'preview', 'width=950,height=600,scrollbars=yes');
                    
                    // Проверяем закрытие окна
                    const checkClosed = setInterval(() => {
                        if (previewWindow.closed) {
                            clearInterval(checkClosed);
                            console.log('Preview window closed, deleting article...');
                            
                            // При закрытии отправляем запрос на удаление
                            const deleteUrl = '<?= $deleteTaskBaseUrl; ?>' + '&id=' + result.data.id;
                            
                            fetch(deleteUrl, {
                                method: 'POST',
                                headers: { 'X-CSRF-Token': '<?= Session::getFormToken(); ?>' }
                            })
                            .then(res => res.json())
                            .then(delData => console.log('Delete status:', delData))
                            .catch(err => console.error('Delete error:', err));
                        }
                    }, 1000);
                    
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
