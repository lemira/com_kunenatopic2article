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

class TreeBuilder
{
    public function buildFlatPostIdList(array $postIds): array
    {
        sort($postIds);
        array_push($postIds, 0);

        return $postIds;
    }

    public function buildTreePostIdList(int $firstPostId, array $finalPostIds, array $allPosts): array
    {
        $fullChildrenMap = [];
        foreach ($allPosts as $post) {
            if ($post->parent > 0) {
                $fullChildrenMap[$post->parent][] = $post->id;
            }
        }

        $recoveredChildren = [];
        foreach ($allPosts as $post) {
            if (in_array($post->id, $finalPostIds)
                && $post->parent > 0
                && !in_array($post->parent, $finalPostIds)) {
                $newParent = $this->findClosestExistingParent($post->parent, $finalPostIds, $allPosts);

                if ($newParent > 0) {
                    $recoveredChildren[$newParent][] = $post->id;
                } else {
                    $recoveredChildren[$firstPostId][] = $post->id;
                }
            }
        }

        $children = [];
        foreach ($finalPostIds as $postId) {
            if ($postId == 0) {
                continue;
            }

            $children[$postId] = [];

            if (isset($fullChildrenMap[$postId])) {
                foreach ($fullChildrenMap[$postId] as $childId) {
                    if (in_array($childId, $finalPostIds)) {
                        $children[$postId][] = $childId;
                    }
                }
            }

            if (isset($recoveredChildren[$postId])) {
                $children[$postId] = array_merge($children[$postId], $recoveredChildren[$postId]);
            }

            if (!empty($children[$postId])) {
                $children[$postId] = array_unique($children[$postId]);
                sort($children[$postId]);
            } else {
                $children[$postId] = [0];
            }
        }

        $postIdList = [];
        $postLevelList = [];

        $this->traverseTree($firstPostId, 0, $children, $postIdList, $postLevelList);

        return [
            'postIds' => array_merge($postIdList, [0]),
            'levels' => $postLevelList
        ];
    }

    private function findClosestExistingParent($deletedParentId, array $finalPostIds, array $allPosts): int
    {
        $postMap = [];
        foreach ($allPosts as $post) {
            $postMap[$post->id] = $post;
        }

        $currentId = $deletedParentId;

        while (isset($postMap[$currentId])) {
            $currentPost = $postMap[$currentId];

            if (in_array($currentPost->id, $finalPostIds)) {
                return $currentPost->id;
            }

            if ($currentPost->parent > 0) {
                $currentId = $currentPost->parent;
            } else {
                break;
            }
        }

        return 0;
    }

    private function traverseTree($postId, int $level, array $children, array &$postIdList, array &$postLevelList): void
    {
        $postIdList[] = $postId;
        $postLevelList[] = $level;

        if (isset($children[$postId]) && $children[$postId][0] !== 0) {
            foreach ($children[$postId] as $childId) {
                $this->traverseTree($childId, $level + 1, $children, $postIdList, $postLevelList);
            }
        }
    }
}
