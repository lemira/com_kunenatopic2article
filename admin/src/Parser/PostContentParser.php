<?php
/**
 * @package     KunenaTopic2Article
 * @subpackage  Administrator
 *
 * @copyright   (C) 2025 Leonid Ratner. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\KunenaTopic2Article\Administrator\Parser;

defined('_JEXEC') or die;

use Joomla\Component\KunenaTopic2Article\Administrator\Helper\VideoProcessor;
use Joomla\Component\KunenaTopic2Article\Administrator\Repository\KunenaRepository;

class PostContentParser
{
    private KunenaRepository $kunenaRepository;
    private VideoProcessor $videoProcessor;

    public function __construct(KunenaRepository $kunenaRepository, VideoProcessor $videoProcessor)
    {
        $this->kunenaRepository = $kunenaRepository;
        $this->videoProcessor = $videoProcessor;
    }

    public function render(string $text): string
    {
        class_exists(Tag::class, true);

        $bbcode = new BBCode();

        $text = $this->protectExistingHtmlLinks($text);

        $text = $this->normalizeBrokenLineBreaks($text);
        $text = $this->escapeUnclosedSquareBracketTags($text);

        // Сначала обрабатываем BBCode тег [video]
        $text = $this->videoProcessor->extractVideoFromBBCode($text);

        // Обрабатываем ВСЕ видео-ссылки (включая BBCode)
        $text = $this->videoProcessor->processVideoLinks($text);

        // Защищаем URL внутри [img] тегов
        $imgProtect = [];
        $text = preg_replace_callback(
            '/\[img\](https?:\/\/[^\[]+?)\[\/img\]/i',
            function ($m) use (&$imgProtect) {
                $marker = '___IMGURL_' . count($imgProtect) . '___';
                $imgProtect[$marker] = $m[0];
                return $marker;
            },
            $text
        );

        // Делаем линками "голые" URL (но уже не видео-ссылки)
        $text = preg_replace_callback(
            '#(?<![\[="\'])(?<!href=)(https?://(?:(?!&(?:amp;)?(?:quot|apos|lt|gt);)[^\s\[\]<>"\'\)])+)#i',
            function ($m) {
                $url = rtrim($m[1], '.,;:!?');
                return '[url]' . $url . '[/url]';
            },
            $text
        );

        // Восстанавливаем защищённые [img] теги
        foreach ($imgProtect as $marker => $original) {
            $text = str_replace($marker, $original, $text);
        }

        // Заменяем attachment на временные маркеры
        $attachments = [];
        $text = preg_replace_callback(
            '/\[attachment=(\d+)\](.*?)\[\/attachment\]/i',
            function ($matches) use (&$attachments) {
                $attachmentId = $matches[1];
                $filename = $matches[2];
                $marker = '###ATTACHMENT_' . count($attachments) . '###';
                $attachments[$marker] = [$attachmentId, $filename];
                return $marker;
            },
            $text
        );

        // Применяем BBCode парсер
        $html = $bbcode->render($text);

        // Нормализуем br теги
        $html = preg_replace('/\s*<br\s*\/?>\s*/i', "\n", $html);

        // ЕДИНСТВЕННОЕ МЕСТО восстановления защищенного контента
        $html = preg_replace_callback(
            '/___PROTECTED___(.*?)___END___/',
            function ($matches) {
                return base64_decode($matches[1]);
            },
            $html
        );

        // Декодирование HTML-сущностей до построения абзацев, чтобы существующие HTML-блоки
        // не оборачивались в лишние <p>.
        $html = str_replace('&lt;', '<', $html);
        $html = str_replace('&gt;', '>', $html);
        $html = str_replace('&quot;', '"', $html);
        $html = str_replace('&amp;', '&', $html);

        // Разбиваем по переносам строк
        $lines = explode("\n", $html);

        // Обрабатываем каждую строку
        $paragraphs = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                $paragraphs[] = '<p>&nbsp;</p>';
                continue;
            }
            if (!preg_match('/^\s*<(p|div|h[1-6]|ul|ol|li|blockquote|pre|table|tr|td|th|iframe)\b/i', $line)) {
                $line = '<p>' . $line . '</p>';
            }

            $paragraphs[] = $line;
        }

        $html = implode("\n", $paragraphs);
        $html = $this->escapeInvalidAngleBracketTags($html);

        // Восстанавливаем изображения
        foreach ($attachments as $marker => $data) {
            $attachmentId = $data[0];
            $filename = $data[1];

            $imagePath = $this->getAttachmentPath($attachmentId);

            if ($imagePath && file_exists(JPATH_ROOT . '/' . $imagePath)) {
                $imageHtml = '<img src="' . $imagePath . '" alt="' . htmlspecialchars($filename) . '" />';
            } else {
                $imageHtml = $filename;
            }

            $html = str_replace($marker, $imageHtml, $html);
        }

        // Обрезка длинных ссылок
        $html = preg_replace_callback(
            '#<a\s+([^>]*?)href=[\'"]([^\'"]+)[\'"]([^>]*)>([^<]{50,})</a>#i',
            function ($m) {
                if (preg_match('/\{(?:youtube|vimeo|facebook|soundcloud|dailymotion)\}/', $m[4])) {
                    return $m[0];
                }

                $visible = mb_substr($m[4], 0, 47) . '…';
                return '<a ' . $m[1] . 'href="' . $m[2] . '"' . $m[3] . '>'
                    . htmlspecialchars($visible, ENT_QUOTES, 'UTF-8')
                    . '</a>';
            },
            $html
        );

        $html = $this->closeOpenHtmlTags($html);

        return '<div class="kun_p2a_content">' . $html . '</div>';
    }

    private function escapeInvalidAngleBracketTags(string $html): string
    {
        // Восстановлено из старой модели: BBCode/JCE иногда оставляет текстовые
        // фрагменты вида <2> или <4»>, которые браузер принимает за HTML.
        $html = preg_replace('#<(\d+)(?=<br\s*/?\s*>)#i', '&lt;$1&gt;', $html);

        return preg_replace_callback(
            '#<([^>]*+)>#',
            function ($matches) {
                $inside = trim($matches[1]);

                if (preg_match('/^(?:![A-Z]+|\/?[a-zA-Z][a-zA-Z0-9:-]*(?:\s+[^>]*)?\/?)$/', $inside)) {
                    return $matches[0];
                }

                return '&lt;' . htmlspecialchars($inside, ENT_QUOTES, 'UTF-8') . '&gt;';
            },
            $html
        );
    }

    private function getAttachmentPath($attachmentId): ?string
    {
        try {
            $attachment = $this->kunenaRepository->getAttachment((int) $attachmentId);

            if ($attachment) {
                $imagePath = $attachment->folder . '/' . $attachment->filename;

                if (file_exists(JPATH_ROOT . '/' . $imagePath)) {
                    return $imagePath;
                }
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    private function normalizeBrokenLineBreaks(string $text): string
    {
        // Kunena/JCE может оставить сломанные фрагменты "[br /"; BBCode-парсер
        // иногда отбрасывает после них хвост поста, поэтому нормализуем их заранее.
        $space = '(?:\s|&nbsp;|\x{00A0})*';
        $text = preg_replace('/<([^>]*?)\[br' . $space . '\/' . $space . '[>\]]/iu', '<$1>', $text);
        $text = preg_replace('/\[br' . $space . '\/?' . $space . '\]/iu', "\n", $text);
        $text = preg_replace('/<br' . $space . '\/?' . $space . '>/iu', "\n", $text);
        $text = preg_replace('/<br' . $space . '\/' . $space . '(?=\S|$)/iu', "\n", $text);

        return preg_replace('/\[br' . $space . '\/' . $space . '/iu', "\n", $text);
    }

    private function escapeUnclosedSquareBracketTags(string $text): string
    {
        // BBCode::render() отбрасывает хвост текста после любой незакрытой "[...".
        // Сохраняем такой фрагмент как обычный текст, чтобы статья не обрывалась.
        return preg_replace_callback(
            '/\[[^\]\r\n]*(?=\r?\n|$)/u',
            function ($matches) {
                return str_replace('[', '&#91;', $matches[0]);
            },
            $text
        );
    }

    private function protectExistingHtmlLinks(string $text): string
    {
        $text = preg_replace_callback(
            '#<a\b[^>]*\bhref=(["\'])(.*?)\1[^>]*>.*?</a>#is',
            function ($matches) {
                return '___PROTECTED___' . base64_encode($matches[0]) . '___END___';
            },
            $text
        );

        return preg_replace_callback(
            '#(?:&lt;|&amp;lt;)a\b.*?(?:&lt;|&amp;lt;)/a(?:&gt;|&amp;gt;)#is',
            function ($matches) {
                $linkHtml = $matches[0];
                for ($i = 0; $i < 3; $i++) {
                    $decoded = html_entity_decode($linkHtml, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    if ($decoded === $linkHtml) {
                        break;
                    }
                    $linkHtml = $decoded;
                }

                return '___PROTECTED___' . base64_encode($linkHtml) . '___END___';
            },
            $text
        );
    }

    private function closeOpenHtmlTags(string $html): string
    {
        $stack = [];
        $voidTags = [
            'area' => true,
            'base' => true,
            'br' => true,
            'col' => true,
            'embed' => true,
            'hr' => true,
            'img' => true,
            'input' => true,
            'link' => true,
            'meta' => true,
            'param' => true,
            'source' => true,
            'track' => true,
            'wbr' => true,
        ];

        preg_match_all('#<\s*(/)?\s*([a-z][a-z0-9:-]*)\b[^>]*(/)?\s*>#i', $html, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $isClosing = $match[1] === '/';
            $tag = strtolower($match[2]);
            $isSelfClosing = isset($match[3]) && $match[3] === '/';

            if (isset($voidTags[$tag]) || $isSelfClosing) {
                continue;
            }

            if (!$isClosing) {
                $stack[] = $tag;
                continue;
            }

            $position = array_search($tag, array_reverse($stack, true), true);

            if ($position !== false) {
                $stack = array_slice($stack, 0, (int) $position);
            }
        }

        while (!empty($stack)) {
            $html .= '</' . array_pop($stack) . '>';
        }

        return $html;
    }
}
