<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_kunenatopic2article
 *
 * @copyright   Copyright (C) 2023 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\AdminController;

/**
 * KunenaTopic2Article Controller
 *
 * @since  0.0.1
 */
class KunenaTopic2ArticleControllerDiagnostic extends AdminController
{
    /**
     * Диагностика проблем с компонентом и базой данных
     *
     * @return  void
     */
    public function diagnose()
    {
        // Проверяем права доступа
        if (!Factory::getUser()->authorise('core.admin', 'com_kunenatopic2article')) {
            throw new Exception(JText::_('JERROR_ALERTNOAUTHOR'));
        }

        try {
            $app = Factory::getApplication();
            $db = Factory::getDbo();
            $results = [];

            // 1. Проверка наличия статей в базе
            $query = $db->getQuery(true)
                ->select('COUNT(*)')
                ->from('#__content');
            $totalArticles = $db->setQuery($query)->loadResult();
            $results[] = "Всего статей в базе: {$totalArticles}";

            // 2. Проверка структуры таблицы статей
            $tableFields = $db->getTableColumns('#__content');
            $requiredFields = ['id', 'title', 'alias', 'state', 'catid', 'created', 'created_by', 'access', 'language'];
            $missingFields = [];
            
            foreach ($requiredFields as $field) {
                if (!array_key_exists($field, $tableFields)) {
                    $missingFields[] = $field;
                }
            }
            
            if (!empty($missingFields)) {
                $results[] = "Отсутствующие поля в таблице статей: " . implode(', ', $missingFields);
            } else {
                $results[] = "Структура таблицы статей в порядке";
            }

            // 3. Проверка последних статей
            $query = $db->getQuery(true)
                ->select('id, title, alias, catid, state')
                ->from('#__content')
                ->order('id DESC')
                ->setLimit(5);
            $latestArticles = $db->setQuery($query)->loadObjectList();
            
            if (!empty($latestArticles)) {
                $results[] = "Последние статьи:";
                foreach ($latestArticles as $article) {
                    $results[] = "ID: {$article->id}, Заголовок: {$article->title}, Алиас: {$article->alias}, " .
                                 "Категория: {$article->catid}, Статус: {$article->state}";
                }
            } else {
                $results[] = "Не удалось получить последние статьи";
            }

            // 4. Проверка категорий
            $query = $db->getQuery(true)
                ->select('id, title, alias, extension, published')
                ->from('#__categories')
                ->where('extension = ' . $db->quote('com_content'));
            $categories = $db->setQuery($query)->loadObjectList();
            
            if (!empty($categories)) {
                $results[] = "Категории статей:";
                foreach ($categories as $category) {
                    $results[] = "ID: {$category->id}, Заголовок: {$category->title}, " .
                                 "Опубликовано: {$category->published}";
                }
            } else {
                $results[] = "Не удалось получить категории статей";
            }

            // 5. Проверка компонента content
            $query = $db->getQuery(true)
                ->select('enabled')
                ->from('#__extensions')
                ->where('type = ' . $db->quote('component'))
                ->where('element = ' . $db->quote('com_content'));
            $contentEnabled = $db->setQuery($query)->loadResult();
            
            $results[] = "Компонент com_content " . ($contentEnabled ? "включен" : "выключен");

            // 6. Возможная проблема с уникальными алиасами
            $query = $db->getQuery(true)
                ->select('alias, COUNT(*) as count')
                ->from('#__content')
                ->group('alias')
                ->having('COUNT(*) > 1');
            $duplicateAliases = $db->setQuery($query)->loadObjectList();
            
            if (!empty($duplicateAliases)) {
                $results[] = "Найдены дублирующиеся алиасы:";
                foreach ($duplicateAliases as $alias) {
                    $results[] = "Алиас: {$alias->alias}, Количество: {$alias->count}";
                }
            } else {
                $results[] = "Дублирующихся алиасов не найдено";
            }

            // Отображаем результаты
            $app->enqueueMessage('<pre>' . implode("\n", $results) . '</pre>', 'notice');
            $app->redirect('index.php?option=com_kunenatopic2article');
        } catch (Exception $e) {
            Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
            $app->redirect('index.php?option=com_kunenatopic2article');
        }
    }

    /**
     * Исправление проблем с компонентом и базой данных
     *
     * @return  void
     */
    public function fixIssues()
    {
        // Проверяем права доступа
        if (!Factory::getUser()->authorise('core.admin', 'com_kunenatopic2article')) {
            throw new Exception(JText::_('JERROR_ALERTNOAUTHOR'));
        }

        try {
            $app = Factory::getApplication();
            $db = Factory::getDbo();
            $results = [];

            // 1. Сброс фильтров для com_content
            $query = $db->getQuery(true)
                ->delete('#__user_states')
                ->where('context LIKE ' . $db->quote('com_content%'));
            $db->setQuery($query)->execute();
            $results[] = "Фильтры com_content сброшены";

            // 2. Проверка и исправление дубликатов алиасов
            $query = $db->getQuery(true)
                ->select('alias, COUNT(*) as count')
                ->from('#__content')
                ->group('alias')
                ->having('COUNT(*) > 1');
            $duplicateAliases = $db->setQuery($query)->loadObjectList();
            
            if (!empty($duplicateAliases)) {
                $results[] = "Исправление дублирующихся алиасов:";
                
                foreach ($duplicateAliases as $duplicate) {
                    // Получаем статьи с дублирующимся алиасом
                    $query = $db->getQuery(true)
                        ->select('id, alias')
                        ->from('#__content')
                        ->where('alias = ' . $db->quote($duplicate->alias))
                        ->order('id ASC');
                    $articles = $db->setQuery($query)->loadObjectList();
                    
                    // Пропускаем первую статью (оставляем оригинальный алиас)
                    array_shift($articles);
                    
                    // Обновляем алиасы для дубликатов
                    foreach ($articles as $article) {
                        $newAlias = $article->alias . '-' . uniqid();
                        $updateQuery = $db->getQuery(true)
                            ->update('#__content')
                            ->set('alias = ' . $db->quote($newAlias))
                            ->where('id = ' . (int)$article->id);
                        $db->setQuery($updateQuery)->execute();
                        $results[] = "Обновлен алиас для статьи ID {$article->id}: {$newAlias}";
                    }
                }
            } else {
                $results[] = "Дублирующихся алиасов не найдено";
            }

            // 3. Обновление параметров компонента content
            $query = $db->getQuery(true)
                ->update('#__extensions')
                ->set('enabled = 1')
                ->where('type = ' . $db->quote('component'))
                ->where('element = ' . $db->quote('com_content'));
            $db->setQuery($query)->execute();
            $results[] = "Компонент com_content включен";

            // Отображаем результаты
            $app->enqueueMessage('<pre>' . implode("\n", $results) . '</pre>', 'notice');
            $app->redirect('index.php?option=com_kunenatopic2article');
        } catch (Exception $e) {
            Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
            $app->redirect('index.php?option=com_kunenatopic2article');
        }
    }
}
