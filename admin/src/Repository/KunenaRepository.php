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
use Joomla\CMS\Router\Route;

class KunenaRepository
{
    public function __construct(private DatabaseInterface $db)
    {
    }

    public function getTopicByFirstPostId(int $postId): ?array
    {
        $query = $this->db->getQuery(true)
            ->select($this->db->quoteName(['subject']))
            ->from($this->db->quoteName('#__kunena_topics'))
            ->where($this->db->quoteName('first_post_id') . ' = ' . $postId)
            ->where($this->db->quoteName('hold') . ' = 0');

        $topic = $this->db->setQuery($query)->loadAssoc();

        return $topic ?: null;
    }

    public function getMessage(int $postId): ?object
    {
        $query = $this->db->getQuery(true)
            ->select($this->db->quoteName(['id', 'subject', 'thread', 'userid', 'parent', 'name', 'time', 'catid']))
            ->from($this->db->quoteName('#__kunena_messages'))
            ->where($this->db->quoteName('id') . ' = ' . $postId);

        $message = $this->db->setQuery($query)->loadObject();

        return $message ?: null;
    }

    public function getMessageText(int $postId): ?string
    {
        $query = $this->db->getQuery(true)
            ->select($this->db->quoteName('message'))
            ->from($this->db->quoteName('#__kunena_messages_text'))
            ->where($this->db->quoteName('mesid') . ' = ' . $postId);

        $message = $this->db->setQuery($query)->loadResult();

        return $message === null ? null : (string) $message;
    }

    public function getVisibleThreadPostIds(int $threadId, array $ignoredAuthors = []): array
    {
        $query = $this->db->getQuery(true)
            ->select($this->db->quoteName('id'))
            ->from($this->db->quoteName('#__kunena_messages'))
            ->where($this->db->quoteName('thread') . ' = ' . $threadId)
            ->where($this->db->quoteName('hold') . ' = 0');

        if (!empty($ignoredAuthors)) {
            $quotedAuthors = array_map([$this->db, 'quote'], $ignoredAuthors);
            $query->where($this->db->quoteName('name') . ' NOT IN (' . implode(',', $quotedAuthors) . ')');
        }

        $query->order($this->db->quoteName('id') . ' ASC');

        return $this->db->setQuery($query)->loadColumn();
    }

    public function getThreadTreePosts(int $threadId): array
    {
        $query = $this->db->getQuery(true)
            ->select($this->db->quoteName(['id', 'parent', 'hold']))
            ->from($this->db->quoteName('#__kunena_messages'))
            ->where($this->db->quoteName('thread') . ' = ' . $threadId);

        return $this->db->setQuery($query)->loadObjectList();
    }

    public function getMessageUrl(int $postId): ?string
    {
        try {
            $message = \Kunena\Forum\Libraries\Forum\Message\KunenaMessageHelper::get($postId);

            if (!$message || !method_exists($message, 'getUrl')) {
                return null;
            }

            $url = $message->getUrl(null, true);

            return empty($url) ? null : (string) $url;
        } catch (\Throwable) {
            return null;
        }
    }

    public function getCategoryAlias(int $categoryId): ?string
    {
        $query = $this->db->getQuery(true)
            ->select($this->db->quoteName('alias'))
            ->from($this->db->quoteName('#__kunena_categories'))
            ->where($this->db->quoteName('id') . ' = ' . $categoryId);

        $alias = $this->db->setQuery($query)->loadResult();

        return empty($alias) ? null : (string) $alias;
    }

    public function getTopicSubject(int $topicId): ?string
    {
        $query = $this->db->getQuery(true)
            ->select($this->db->quoteName('subject'))
            ->from($this->db->quoteName('#__kunena_topics'))
            ->where($this->db->quoteName('id') . ' = ' . $topicId);

        $subject = $this->db->setQuery($query)->loadResult();

        return empty($subject) ? null : (string) $subject;
    }

    public function getKunenaMenuPath(): string
    {
        $query = $this->db->getQuery(true)
            ->select($this->db->quoteName(['path', 'alias']))
            ->from($this->db->quoteName('#__menu'))
            ->where($this->db->quoteName('client_id') . ' = 0')
            ->where($this->db->quoteName('published') . ' = 1')
            ->where($this->db->quoteName('link') . ' LIKE ' . $this->db->quote('%option=com_kunena%'))
            ->order($this->db->quoteName('level') . ' ASC, ' . $this->db->quoteName('id') . ' ASC');

        $item = $this->db->setQuery($query, 0, 1)->loadObject();

        if (!$item) {
            return 'forum';
        }

        $path = trim((string) ($item->path ?: $item->alias), '/');

        return $path !== '' ? $path : 'forum';
    }

    public function getKunenaMenuItemId(): int
    {
        $query = $this->db->getQuery(true)
            ->select($this->db->quoteName('id'))
            ->from($this->db->quoteName('#__menu'))
            ->where($this->db->quoteName('client_id') . ' = 0')
            ->where($this->db->quoteName('published') . ' = 1')
            ->where($this->db->quoteName('link') . ' LIKE ' . $this->db->quote('%option=com_kunena%'))
            ->order($this->db->quoteName('level') . ' ASC, ' . $this->db->quoteName('id') . ' ASC');

        return (int) $this->db->setQuery($query, 0, 1)->loadResult();
    }

    public function getKunenaMessagesPerPage(int $default = 20): int
    {
        try {
            if (class_exists('\Kunena\Forum\Libraries\Factory\KunenaFactory')) {
                $config = \Kunena\Forum\Libraries\Factory\KunenaFactory::getConfig();
                $value = (int) ($config->messagesPerPage ?? 0);

                if ($value > 0) {
                    return $value;
                }
            }
        } catch (\Throwable) {
        }

        try {
            $query = $this->db->getQuery(true)
                ->select($this->db->quoteName('params'))
                ->from($this->db->quoteName('#__kunena_configuration'));

            $jsonParams = $this->db->setQuery($query, 0, 1)->loadResult();
            $params = json_decode((string) $jsonParams, true);
            $value = (int) ($params['messagesPerPage'] ?? 0);

            if ($value > 0) {
                return $value;
            }
        } catch (\Throwable) {
        }

        return $default;
    }

    public function buildTopicPostUrl(int $categoryId, int $topicId, string $topicSubject, int $postStart, int $postId): string
    {
        $query = [
            'option' => 'com_kunena',
            'view' => 'topic',
            'catid' => $categoryId,
            'id' => $topicId,
        ];

        $itemId = $this->getKunenaMenuItemId();

        if ($itemId > 0) {
            $query['Itemid'] = $itemId;
        }

        if ($postStart > 0) {
            $query['start'] = $postStart;
        }

        return Route::link('site', 'index.php?' . http_build_query($query, '', '&'), false) . '#' . $postId;
    }

    public function getAttachment(int $attachmentId): ?object
    {
        $query = $this->db->getQuery(true)
            ->select($this->db->quoteName(['folder', 'filename', 'filename_real']))
            ->from($this->db->quoteName('#__kunena_attachments'))
            ->where($this->db->quoteName('id') . ' = ' . $attachmentId);

        $attachment = $this->db->setQuery($query)->loadObject();

        return $attachment ?: null;
    }

    public function getMajorParams(int $postId): ?object
    {
        $query = $this->db->getQuery(true)
            ->select($this->db->quoteName(['thread', 'subject', 'userid']))
            ->from($this->db->quoteName('#__kunena_messages'))
            ->where($this->db->quoteName('id') . ' = ' . $postId);

        $params = $this->db->setQuery($query)->loadObject();

        return $params ?: null;
    }
}
