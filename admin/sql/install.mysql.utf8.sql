CREATE TABLE IF NOT EXISTS `#__kunenatopic2article_params` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `topic_selection` INT NOT NULL DEFAULT 0,
    `article_category` INT NOT NULL DEFAULT 0,
    `post_transfer_scheme` TINYINT(1) NOT NULL DEFAULT 1,
    `max_article_size` INT NOT NULL DEFAULT 40000,
    `post_author` TINYINT(1) NOT NULL DEFAULT 1,
    `post_creation_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `post_creation_time` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `post_ids` TINYINT(1) NOT NULL DEFAULT 1,
    `post_title` TINYINT(1) NOT NULL DEFAULT 0,
    `kunena_post_link` TINYINT(1) NOT NULL DEFAULT 0,
    `reminder_lines` INT NOT NULL DEFAULT 0,
    `ignored_authors` TEXT DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `#__kunenatopic2article_params` (
    `topic_selection`, `article_category`, `post_transfer_scheme`, `max_article_size`,
    `post_author`, `post_creation_date`, `post_creation_time`, `post_ids`,
    `post_title`, `kunena_post_link`, `reminder_lines`, `ignored_authors`
) VALUES (
    0, 0, 1, 40000, 1, NOW(), NOW(), 1, 0, 0, 0, NULL
);
