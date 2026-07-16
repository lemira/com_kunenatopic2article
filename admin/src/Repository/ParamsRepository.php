<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_kunenatopic2article
 *
 * @copyright   (C) 2025 Leonid Ratner. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\KunenaTopic2Article\Administrator\Repository;

defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;

class ParamsRepository
{
    private const TABLE = '#__kunenatopic2article_params';

    public function __construct(private DatabaseInterface $db)
    {
    }

    public function getParams(): ?object
    {
        $this->ensureTable();

        $query = $this->db->getQuery(true)
            ->select('*')
            ->from($this->db->quoteName(self::TABLE))
            ->where($this->db->quoteName('id') . ' = 1');

        $params = $this->db->setQuery($query)->loadObject();

        return $params ?: null;
    }

    public function resetTopicSelection(): void
    {
        $this->ensureTable();

        $query = $this->db->getQuery(true)
            ->update($this->db->quoteName(self::TABLE))
            ->set($this->db->quoteName('topic_selection') . ' = ' . $this->db->quote('0'))
            ->where($this->db->quoteName('id') . ' = 1');

        $this->db->setQuery($query)->execute();
    }

    public function ensureTable(): void
    {
        $tables = $this->db->getTableList();
        $tableName = $this->db->getPrefix() . 'kunenatopic2article_params';

        if (in_array($tableName, $tables)) {
            $this->ensureColumns();
            return;
        }

        $this->createTable();
    }

    private function createTable(): void
    {
        $createQuery = "CREATE TABLE IF NOT EXISTS `#__kunenatopic2article_params` (
            `id` int NOT NULL AUTO_INCREMENT,
            `topic_selection` int NOT NULL DEFAULT 0,
            `article_category` int NOT NULL DEFAULT 0,
            `post_transfer_scheme` int NOT NULL DEFAULT 1,
            `max_article_size` int NOT NULL DEFAULT 40000,
            `post_author` int NOT NULL DEFAULT 1,
            `post_creation_date` int NOT NULL DEFAULT 0,
            `post_creation_time` int NOT NULL DEFAULT 0,
            `post_ids` int NOT NULL DEFAULT 0,
            `post_title` int NOT NULL DEFAULT 0,
            `kunena_post_link` int NOT NULL DEFAULT 0,
            `reminder_lines` int NOT NULL DEFAULT 0,
            `post_info_style_enabled` tinyint NOT NULL DEFAULT 0,
            `post_info_layout` varchar(20) NOT NULL DEFAULT 'two_lines',
            `post_info_background` varchar(20) NOT NULL DEFAULT '#FFFDE7',
            `post_info_text_color` varchar(20) NOT NULL DEFAULT '#8B8000',
            `post_info_font_size` varchar(20) NOT NULL DEFAULT '85%',
            `post_info_ids_font_size` varchar(20) NOT NULL DEFAULT '70%',
            `post_info_align` varchar(20) NOT NULL DEFAULT 'center',
            `post_info_width` varchar(20) NOT NULL DEFAULT '80%',
            `post_info_accent_color` varchar(20) NOT NULL DEFAULT '#FFD700',
            `ignored_authors` text,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->db->setQuery($createQuery);
        $this->db->execute();

        $insertQuery = "INSERT IGNORE INTO `#__kunenatopic2article_params` (
                        `id`, `topic_selection`, `article_category`, `post_transfer_scheme`, `max_article_size`,
                        `post_author`, `post_creation_date`, `post_creation_time`, `post_ids`, `post_title`,
                        `kunena_post_link`, `reminder_lines`, `post_info_style_enabled`, `post_info_layout`,
                        `post_info_background`, `post_info_text_color`, `post_info_font_size`,
                        `post_info_ids_font_size`, `post_info_align`, `post_info_width`,
                        `post_info_accent_color`, `ignored_authors`)
                        VALUES (1, 0, 0, 1, 40000, 1, 0, 0, 0, 0, 0, 0, 0, 'two_lines',
                        '#FFFDE7', '#8B8000', '85%', '70%', 'center', '80%', '#FFD700', '')";

        $this->db->setQuery($insertQuery);
        $this->db->execute();
    }

    private function ensureColumns(): void
    {
        $tableName = $this->db->getPrefix() . 'kunenatopic2article_params';
        $columns = $this->db->getTableColumns($tableName);
        $definitions = [
            'post_info_style_enabled' => "`post_info_style_enabled` tinyint NOT NULL DEFAULT 0",
            'post_info_layout' => "`post_info_layout` varchar(20) NOT NULL DEFAULT 'two_lines'",
            'post_info_background' => "`post_info_background` varchar(20) NOT NULL DEFAULT '#FFFDE7'",
            'post_info_text_color' => "`post_info_text_color` varchar(20) NOT NULL DEFAULT '#8B8000'",
            'post_info_font_size' => "`post_info_font_size` varchar(20) NOT NULL DEFAULT '85%'",
            'post_info_ids_font_size' => "`post_info_ids_font_size` varchar(20) NOT NULL DEFAULT '70%'",
            'post_info_align' => "`post_info_align` varchar(20) NOT NULL DEFAULT 'center'",
            'post_info_width' => "`post_info_width` varchar(20) NOT NULL DEFAULT '80%'",
            'post_info_accent_color' => "`post_info_accent_color` varchar(20) NOT NULL DEFAULT '#FFD700'",
        ];

        foreach ($definitions as $column => $definition) {
            if (array_key_exists($column, $columns)) {
                continue;
            }

            $this->db->setQuery("ALTER TABLE " . $this->db->quoteName($tableName) . " ADD COLUMN {$definition}");
            $this->db->execute();
        }
    }
}
