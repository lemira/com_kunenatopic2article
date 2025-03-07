<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;

// Подключаем зависимости Joomla
jimport('joomla.application.component.controller');

// Инициализация или обновление таблицы параметров
$db = Factory::getDbo();

try {
    // Проверяем, существует ли таблица
    $query = "SHOW TABLES LIKE '" . $db->getPrefix() . "kunenatopic2article_params'";
    $db->setQuery($query);
    $tableExists = $db->loadResult();

    if (!$tableExists) {
        // Создаём таблицу, если она не существует
        $queries = [
            "CREATE TABLE `#__kunenatopic2article_params` (
                `id` INT NOT NULL AUTO_INCREMENT,
                `topic_selection` INT NOT NULL DEFAULT 0,
                `article_category` INT NOT NULL DEFAULT 0,
                `post_transfer_scheme` TINYINT(1) NOT NULL DEFAULT 1,
                `max_article_size` INT NOT NULL DEFAULT 40000,
                `post_author` TINYINT(1) NOT NULL DEFAULT 1,
                `post_creation_date` TINYINT(1) NOT NULL DEFAULT 1,
                `post_creation_time` TINYINT(1) NOT NULL DEFAULT 0,
                `post_ids` TINYINT(1) NOT NULL DEFAULT 1,
                `post_title` TINYINT(1) NOT NULL DEFAULT 0,
                `kunena_post_link` TINYINT(1) NOT NULL DEFAULT 0,
                `reminder_lines` INT NOT NULL DEFAULT 0,
                `ignored_authors` TEXT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "INSERT INTO `#__kunenatopic2article_params` (
                `topic_selection`, `article_category`, `post_transfer_scheme`, `max_article_size`,
                `post_author`, `post_creation_date`, `post_creation_time`, `post_ids`,
                `post_title`, `kunena_post_link`, `reminder_lines`, `ignored_authors`
            ) VALUES (
                0, 0, 1, 40000, 1, 1, 0, 1, 0, 0, 0, NULL
            )"
        ];

        foreach ($queries as $query) {
            $db->setQuery($query);
            $db->execute();
        }
        Factory::getApplication()->enqueueMessage('Таблица параметров создана с настройками по умолчанию.');
    } else {
        // Если таблица существует, сбрасываем только topic_selection
        $query = "UPDATE `#__kunenatopic2article_params` SET `topic_selection` = 0 WHERE `id` = 1";
        $db->setQuery($query);
        $db->execute();
        Factory::getApplication()->enqueueMessage('Параметры загружены, выбор темы сброшен.');
    }
} catch (Exception $e) {
    Factory::getApplication()->enqueueMessage('Ошибка при работе с таблицей: ' . $e->getMessage(), 'error');
}

// Запускаем контроллер
$controller = JControllerLegacy::getInstance('KunenaTopic2Article');
$controller->execute(Factory::getApplication()->input->get('task'));
$controller->redirect();
