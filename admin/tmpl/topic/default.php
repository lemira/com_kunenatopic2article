<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('bootstrap.framework');
HTMLHelper::_('behavior.core');

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

<script>
// Стандартный обработчик для кнопок "Сохранить", "Сбросить", "Создать"
Joomla.submitbutton = function(task) {
    const form = document.getElementById('adminForm');
    
    // Валидация для кнопки "Сохранить"
    if (task === 'save' && !document.formvalidator.isValid(form)) {
        alert('<?= Text::_('JGLOBAL_VALIDATION_FORM_FAILED', true); ?>');
        return false;
    }
    
    Joomla.submitform(task, form);
};

// Навешиваем события после полной загрузки страницы
document.addEventListener('DOMContentLoaded', function() {
    const previewButton = document.getElementById('btn_preview');
    
    if (!previewButton) {
        return;
    }

    // Обработчик для кнопки "Посмотреть"
    previewButton.addEventListener('click', async function(event) {
        event.preventDefault();

        try {
            // Отправляем запрос на создание превью.
            // URL содержит view=article, чтобы избежать ошибки "представление не найдено".
            // Тело запроса (body) пустое, т.к. модель возьмет параметры из настроек.
            const response = await fetch('index.php?option=com_kunenatopic2article&view=article&task=article.preview&format=json', {
                method: 'POST',
                headers: {
                    'X-CSRF-Token': Joomla.getToken()
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const result = await response.json();

            if (result.success && result.data.url) {
                // Создаем и открываем модальное окно с превью
                const iframe = Joomla.Modal.createIframe({
                    src: result.data.url,
                    title: '<?= Text::_('COM_KUNENATOPIC2ARTICLE_PREVIEW_TITLE', true); ?>',
                    width: '80%',
                    height: '80%'
                });
                
                const modal = Joomla.Bootstrap.Modal.open(iframe);
                
                // Навешиваем событие на закрытие модального окна
                modal.addEventListener('hidden.bs.modal', async () => {
                    
                    try {
                        // Отправляем запрос на удаление временной статьи                       
               const deleteResponse = await fetch('index.php?option=com_kunenatopic2article&view=article&task=article.deletePreview&format=json', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                                'X-CSRF-Token': Joomla.getToken()
                            },
                            body: new URLSearchParams({ 'id': result.data.id })
                        });
                    } catch (deleteError) {
                        // Ошибку удаления можно просто записать в консоль, чтобы не беспокоить пользователя
                        console.error('Ошибка при удалении статьи предварительного просмотра:', deleteError);
                    }
                    
                    modal.remove(); // Очищаем DOM от элемента модального окна
                });

            } else {
                Joomla.renderMessages({ 'error': [result.message || 'Не удалось получить данные для предварительного просмотра.'] });
            }
        } catch (error) {
            console.error('Ошибка при создании предварительного просмотра:', error);
            Joomla.renderMessages({ 'error': [error.message] });
        }
    });
});
</script>
