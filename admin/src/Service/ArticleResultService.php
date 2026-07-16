<?php
/**
 * @package     KunenaTopic2Article
 * @subpackage  Administrator
 *
 * @copyright   (C) 2025 Leonid Ratner. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\KunenaTopic2Article\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\CMS\Uri\Uri;

class ArticleResultService
{
    public function buildResultData(array $articleLinks, array $emailResult): array
    {
        return [
            'articles' => $articleLinks,
            'emails' => [
                'sent' => (bool) ($emailResult['success'] ?? false),
                'recipients' => $emailResult['recipients'] ?? [],
            ],
        ];
    }

    public function buildPreviewResponse(array $articleData): array
    {
        $id = (int) ($articleData['id'] ?? 0);

        if ($id <= 0) {
            throw new \InvalidArgumentException('Failed to create preview article');
        }

        return [
            'url' => Uri::root() . 'index.php?option=com_content&view=article&id=' . $id,
            'id' => $id,
        ];
    }
}
