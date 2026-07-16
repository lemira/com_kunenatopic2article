<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_kunenatopic2article
 *
 * @copyright   (C) 2025 Leonid Ratner. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\KunenaTopic2Article\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

class ArticleNotificationService
{
    public function sendArticleLinks(
        array $articleLinks,
        string $topicSubject,
        int $topicPostId,
        int $topicAuthorId
    ): array {
        $app = Factory::getApplication();
        $result = [
            'success'    => false,
            'recipients' => [],
            'error'      => null,
        ];
        $recipients = [];

        try {
            $config = Factory::getConfig();
            $mailer = Factory::getMailer();
            $adminEmail = $config->get('mailfrom');
            $author = Factory::getUser($topicAuthorId);
            $authorEmail = $author->email;

            $recipients = array_unique(array_filter([$adminEmail, $authorEmail], function ($email) {
                return filter_var($email, FILTER_VALIDATE_EMAIL);
            }));

            if (empty($recipients)) {
                $result['error'] = 'Не найдены корректные email-адреса для отправки.';
                return $result;
            }

            $subject = Text::sprintf('COM_KUNENATOPIC2ARTICLE_MAIL_SUBJECT', $config->get('sitename'));
            $body = Text::sprintf(
                'COM_KUNENATOPIC2ARTICLE_MAIL_BODY',
                $config->get('sitename'),
                $topicSubject,
                Uri::root() . 'index.php?option=com_kunena&view=topic&postid=' . $topicPostId,
                $author->name,
                implode("\n", array_map(
                    fn ($link) => "- {$link['title']}: {$link['url']}",
                    $articleLinks
                ))
            );

            $mailer->setSender([$adminEmail, $config->get('sitename')]);
            $mailer->setSubject($subject);
            $mailer->setBody($body);
            $mailer->isHtml(false);

            foreach ($recipients as $email) {
                $mailer->addRecipient($email);
            }

            if ($this->isLocalServer()) {
                $app->enqueueMessage('Режим отладки: отправка почты пропущена (локальный сервер).', 'notice');
                $result['success'] = true;
            } else {
                $mailer->Send();
                $result['success'] = true;
            }

            $result['recipients'] = $recipients;
        } catch (\Exception $e) {
            $errorMessage = Text::sprintf('COM_KUNENATOPIC2ARTICLE_MAIL_SEND_ERROR', $e->getMessage());
            $app->enqueueMessage($errorMessage, 'error');

            $result['success'] = false;
            $result['error'] = $e->getMessage();
            $result['recipients'] = $recipients;
        }

        return $result;
    }

    private function isLocalServer(): bool
    {
        $serverName = strtolower((string) ($_SERVER['SERVER_NAME'] ?? ''));
        $serverAddr = strtolower((string) ($_SERVER['SERVER_ADDR'] ?? ''));

        if (in_array($serverName, ['localhost', '127.0.0.1', '::1'], true)) {
            return true;
        }

        if (in_array($serverAddr, ['127.0.0.1', '::1'], true)) {
            return true;
        }

        return $serverName !== '' && !str_contains($serverName, '.');
    }
}
