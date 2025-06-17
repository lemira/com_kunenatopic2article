<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_kunenatopic2article
 *
 * @copyright   (C) 2025 lr. All rights reserved.
 * @license     GNU/GPL
 */

defined('_JEXEC') or die;

/**
 * Указываем IDE, что $this - это объект нашего View класса.
 * @var Joomla\Component\KunenaTopic2Article\Administrator\View\Result\HtmlView $this
 */
?>

<div class="joomla-overview">
    <h1>Результаты работы компонента</h1>
    
    <?php if (!empty($this->links)) : ?>
        <div class="alert alert-success">
            <p>Были успешно созданы или обновлены следующие материалы:</p>
            <ul>
                <?php foreach ($this->links as $link) : ?>
                    <li>
                        <a href="<?php echo $link['url']; ?>" target="_blank">
                            <?php echo htmlspecialchars($link['title'], ENT_QUOTES, 'UTF-8'); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php else : ?>
        <div class="alert alert-info">
            <p>Не было создано ни одной статьи или информация о них не найдена.</p>
        </div>
    <?php endif; ?>
    
   <p>Администратор, вы можете сообщить автору темы о созданных на ее основе статьях</p>
</div>
