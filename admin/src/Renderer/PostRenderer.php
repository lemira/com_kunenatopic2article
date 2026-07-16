<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_kunenatopic2article
 *
 * @copyright   (C) 2025 Leonid Ratner. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\KunenaTopic2Article\Administrator\Renderer;

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

class PostRenderer
{
    public function renderInfoString(
        object $post,
        object $params,
        callable $postUrlResolver,
        int $currentPostId,
        int $firstPostId,
        ?int $treeLevel
    ): string {
        $idsString = '';

        if ($params->post_ids || $params->kunena_post_link) {
            $idsString = $this->renderPostIds($post, $params, $postUrlResolver);
        }

        $mainString = '';

        if ($params->post_author) {
            $mainString .= htmlspecialchars($post->name, ENT_QUOTES, 'UTF-8');
        }

        if ($params->post_title) {
            $mainString .= ' / <span class="kun_p2a_post_subject">' . htmlspecialchars($post->subject, ENT_QUOTES, 'UTF-8') . '</span>';

            if ($params->post_transfer_scheme == 1 && $currentPostId != $firstPostId) {
                $mainString .= ' / ' . htmlspecialchars("\u{1F332}", ENT_QUOTES, 'UTF-8') . (int) $treeLevel;
            }
        }

        if ($params->post_creation_date) {
            $mainString .= ' / ' . date('d.m.Y', $post->time);

            if ($params->post_creation_time) {
                $mainString .= ' ' . date('H:i', $post->time);
            }
        }

        if (
            $this->isCustomPostInfoStyleEnabled($params)
            && $this->getParam($params, 'post_info_layout', 'two_lines') === 'one_line'
        ) {
            return $this->renderOneLineInfoString($idsString, $mainString, $params);
        }

        return $this->renderTwoLineInfoString($idsString, $mainString, $params);
    }

    public function renderHeadOfPost(object $post, object $params, string $postInfoString, string $reminderLines): string
    {
        $head = $postInfoString;

        if ($params->reminder_lines && $post->parent) {
            $head .= '<div class="kun_p2a_reminder_content" data-tooltip="'
                . Text::_('COM_KUNENATOPIC2ARTICLE_START_OF_REMINDER_LINES') . '">'
                . '<span class="tooltip-icon">ⓘ</span> '
                . $reminderLines
                . '</div>';
        }

        return $head . '<div class="kun_p2a_divider-gray"></div>';
    }

    private function renderPostIds(object $post, object $params, callable $postUrlResolver): string
    {
        $idsString = $this->renderPostId((int) $post->id, $params, $postUrlResolver);

        if ($params->post_ids && !empty($post->parent)) {
            $idsString .= ' ⟸ ' . $this->renderPostId((int) $post->parent, $params, $postUrlResolver);
        }

        return $idsString;
    }

    private function renderPostId(int $postId, object $params, callable $postUrlResolver): string
    {
        if (!$params->kunena_post_link) {
            return '#' . $postId;
        }

        $postUrl = $postUrlResolver($postId);

        if (empty($postUrl)) {
            return '#' . $postId;
        }

        return '<a href="' . htmlspecialchars($postUrl, ENT_QUOTES, 'UTF-8')
            . '" target="_blank" rel="noopener noreferrer">#'
            . $postId . '</a>';
    }

    private function renderTwoLineInfoString(string $idsString, string $mainString, object $params): string
    {
        $idsStyle = $this->buildPostInfoStyle($params, true);
        $mainStyle = $this->buildPostInfoStyle($params, false);

        return HTMLHelper::_('content.prepare', '<div class="kun_p2a_ids kun_p2a_index_line text-center"' . $idsStyle . '>')
            . $idsString
            . '</div>'
            . '<div class="kun_p2a_info_main text-center"' . $mainStyle . '>'
            . $mainString
            . '</div>';
    }

    private function renderOneLineInfoString(string $idsString, string $mainString, object $params): string
    {
        $style = $this->buildPostInfoStyle($params, false);
        $idsStyle = $this->buildPostInfoStyle($params, true);
        $idsPart = $idsString !== ''
            ? '<span class="kun_p2a_ids_inline"' . $idsStyle . '>' . $idsString . '</span>'
            : '';
        $separator = $idsPart !== '' && $mainString !== '' ? ' / ' : '';

        return '<div class="kun_p2a_info_main kun_p2a_info_one_line text-center"' . $style . '>'
            . $idsPart
            . $separator
            . $mainString
            . '</div>';
    }

    private function buildPostInfoStyle(object $params, bool $isIdsLine): string
    {
        if (!$this->isCustomPostInfoStyleEnabled($params)) {
            return '';
        }

        $align = $this->getParam($params, 'post_info_align', 'center') === 'left' ? 'left' : 'center';
        $margin = $align === 'left' ? '0 auto 0 0' : '0 auto';
        $styles = [
            'font-size' => $this->sanitizeCssSize(
                $this->getParam($params, $isIdsLine ? 'post_info_ids_font_size' : 'post_info_font_size', $isIdsLine ? '70%' : '85%'),
                $isIdsLine ? '70%' : '85%'
            ),
            'color' => $this->sanitizeCssColor($this->getParam($params, 'post_info_text_color', '#8B8000'), '#8B8000'),
            'text-align' => $align,
        ];

        if (!$isIdsLine) {
            $styles['background-color'] = $this->sanitizeCssColor(
                $this->getParam($params, 'post_info_background', '#FFFDE7'),
                '#FFFDE7'
            );
            $styles['width'] = $this->sanitizeCssSize($this->getParam($params, 'post_info_width', '80%'), '80%');
            $styles['margin'] = $margin;
            $styles['border-left-color'] = $this->sanitizeCssColor(
                $this->getParam($params, 'post_info_accent_color', '#FFD700'),
                '#FFD700'
            );
        }

        $styleText = '';

        foreach ($styles as $property => $value) {
            $styleText .= $property . ':' . $value . ' !important;';
        }

        return ' style="' . htmlspecialchars($styleText, ENT_QUOTES, 'UTF-8') . '"';
    }

    private function isCustomPostInfoStyleEnabled(object $params): bool
    {
        return (int) $this->getParam($params, 'post_info_style_enabled', 0) === 1;
    }

    private function getParam(object $params, string $name, mixed $default): mixed
    {
        return property_exists($params, $name) && $params->{$name} !== '' && $params->{$name} !== null
            ? $params->{$name}
            : $default;
    }

    private function sanitizeCssColor(string $value, string $default): string
    {
        $value = trim($value);

        if (preg_match('/^#[0-9a-fA-F]{3}([0-9a-fA-F]{3})?$/', $value)) {
            return $value;
        }

        return $default;
    }

    private function sanitizeCssSize(string $value, string $default): string
    {
        $value = trim($value);

        if (preg_match('/^\d+(\.\d+)?(px|em|rem|%)$/', $value)) {
            return $value;
        }

        return $default;
    }
}
