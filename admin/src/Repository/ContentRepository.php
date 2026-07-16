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

class ContentRepository
{
    public function __construct(private DatabaseInterface $db)
    {
    }

    public function aliasExists(string $alias): bool
    {
        $query = $this->db->getQuery(true)
            ->select('1')
            ->from($this->db->quoteName('#__content'))
            ->where($this->db->quoteName('alias') . ' = ' . $this->db->quote($alias))
            ->setLimit(1);

        return (bool) $this->db->setQuery($query)->loadResult();
    }

    public function workflowAssociationExists(int $articleId): bool
    {
        $query = $this->db->getQuery(true)
            ->select('COUNT(*)')
            ->from($this->db->quoteName('#__workflow_associations'))
            ->where($this->db->quoteName('item_id') . ' = ' . $this->db->quote($articleId))
            ->where($this->db->quoteName('extension') . ' = ' . $this->db->quote('com_content.article'));

        return (bool) $this->db->setQuery($query)->loadResult();
    }

    public function addWorkflowAssociation(int $articleId, int $stageId = 1): void
    {
        $query = $this->db->getQuery(true)
            ->insert($this->db->quoteName('#__workflow_associations'))
            ->columns([
                $this->db->quoteName('item_id'),
                $this->db->quoteName('stage_id'),
                $this->db->quoteName('extension')
            ])
            ->values(implode(',', [
                $this->db->quote($articleId),
                $this->db->quote($stageId),
                $this->db->quote('com_content.article')
            ]));

        $this->db->setQuery($query)->execute();
    }

    public function deleteArticleById(int $articleId): bool
    {
        $query = $this->db->getQuery(true)
            ->delete($this->db->quoteName('#__content'))
            ->where($this->db->quoteName('id') . ' = ' . $articleId);

        return (bool) $this->db->setQuery($query)->execute();
    }

    public function deleteWorkflowAssociation(int $articleId): void
    {
        $query = $this->db->getQuery(true)
            ->delete($this->db->quoteName('#__workflow_associations'))
            ->where($this->db->quoteName('item_id') . ' = ' . $articleId)
            ->where($this->db->quoteName('extension') . ' = ' . $this->db->quote('com_content.article'));

        $this->db->setQuery($query)->execute();
    }
}
