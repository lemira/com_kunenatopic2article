CREATE TABLE IF NOT EXISTS `#__kunenatopic2article_params` (
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
    `ignored_authors` text,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `#__kunenatopic2article_params` (`id`, `topic_selection`, `article_category`, `post_transfer_scheme`, `max_article_size`, `post_author`, `post_creation_date`, `post_creation_time`, `post_ids`, `post_title`, `kunena_post_link`, `reminder_lines`, `ignored_authors`)
VALUES (1, 0, 0, 1, 40000, 1, 0, 0, 0, 0, 0, 0, '');
