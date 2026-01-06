<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_kunenatopic2article
 *
 * @copyright   Copyright (C) 2023 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\KunenaTopic2Article\Administrator\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\Component\KunenaTopic2Article\Administrator\Parser\BBCode;
use Joomla\Component\KunenaTopic2Article\Administrator\Parser\Tag;
use Joomla\Registry\Registry;

/**
 * Content Parser Helper
 * Handles BBCode conversion and content processing
 * 
 * @since  1.0.0
 */
class ContentParser
{
    /**
     * Database instance
     * @var    \Joomla\Database\DatabaseInterface
     */
    private $db;
    
    /**
     * Application instance
     * @var    \Joomla\CMS\Application\CMSApplication
     */
    private $app;
    
    /**
     * Callback for getting attachment paths
     * @var    callable|null
     */
    private $attachmentCallback = null;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->app = Factory::getApplication();
        $this->db = Factory::getDbo();
        
        // Загружаем языковые файлы компонента
        $this->loadComponentLanguage();
    }
    
    /**
     * Load component language files
     */
    private function loadComponentLanguage(): void
    {
        static $loaded = false;
        
        if (!$loaded) {
            $lang = $this->app->getLanguage();
            
            // Пробуем загрузить из админки
            $lang->load('com_kunenatopic2article', JPATH_ADMINISTRATOR);
            
            // Также пробуем загрузить из сайта
            $lang->load('com_kunenatopic2article', JPATH_SITE);
            
            $loaded = true;
        }
    }
    
    /**
     * Set callback for getting attachment paths
     *
     * @param   callable  $callback  Callback function
     */
    public function setAttachmentCallback(callable $callback): void
    {
        $this->attachmentCallback = $callback;
    }
    
    /**
     * Main method: Convert BBCode to HTML with all processing
     *
     * @param   string       $text    The BBCode text to convert
     * @param   object|null  $params  Component parameters (optional)
     * 
     * @return  string  HTML content
     */
    public function convertBBCodeToHtml(string $text, ?object $params = null): string
    {
        try {
            // Ensure BBCode classes are loaded
            class_exists(Tag::class, true);
            $bbcode = new BBCode();
            
            // Step 1: Prepare text
            $text = $this->prepareText($text);
            
            // Step 2: Process video links
            $text = $this->processVideoLinks($text);
            
            // Step 3: Protect image URLs
            $text = $this->protectImageTags($text);
            
            // Step 4: Process regular URLs
            $text = $this->processRegularUrls($text);
            
            // Step 5: Restore protected images
            $text = $this->restoreProtectedImages($text);
            
            // Step 6: Process attachments
            $text = $this->processAttachments($text);
            
            // Step 7: Apply BBCode parser
            $html = $bbcode->render($text);
            
            // Step 8: Post-process HTML
            $html = $this->postProcessHtml($html);
            
            return $html;
            
        } catch (\Throwable $e) {
            $this->app->enqueueMessage(
                'BBCode Parse Error: ' . $e->getMessage(),
                'warning'
            );
            return $this->simpleBBCodeToHtml($text);
        }
    }
    
    /**
     * Prepare text before processing
     */
    private function prepareText(string $text): string
    {
        // Удаляем "[br /" которые обрубают текст
        $text = preg_replace('/<([^>]*?)\[br\s*\/\s*[>\]]/iu', '<$1>', $text);
        $text = preg_replace('/([»"\.])\s*>/u', '$1', $text);
        
        // Извлекаем видео из BBCode [video]
        $text = $this->extractVideoFromBBCode($text);
        
        return $text;
    }
    
    /**
     * Extract video from BBCode [video] tags
     */
    private function extractVideoFromBBCode(string $text): string
    {
        return preg_replace('/\[video\](https?:\/\/[^\[]+?)\[\/video\]/i', '$1', $text);
    }
    
    /**
     * Process video links in text
     */
    private function processVideoLinks(string $text): string
    {
        $allVideosEnabled = $this->isAllVideosEnabled();
        
        // Сначала обрабатываем BBCode ссылки [url=...]текст[/url]
        $text = preg_replace_callback(
            '/\[url=([^\]]+)\](.*?)\[\/url\]/i',
            function($matches) use ($allVideosEnabled) {
                $url = trim($matches[1]);
                $linkText = trim($matches[2]);
                
                // Проверяем, является ли это видео-ссылкой
                $platform = $this->detectVideoPlatform($url);
                
                if ($platform) {
                    // Это видео-ссылка
                    $fixedUrl = $this->fixVideoUrl($platform, $url);
                    $tooltip = Text::_('COM_KUNENATOPIC2ARTICLE_VIDEO_INSTALL_ALLVIDEOS');
                    $displayText = $this->getDisplayText($platform, $fixedUrl);
                    
                    // Иконка только для Facebook
                    $icon = ($platform === 'facebook') ? '<span class="facebook-icon">f</span>' : '';
                    
                    // Текст ссылки + стилизованная кнопка
                    $result = htmlspecialchars($linkText, ENT_QUOTES, 'UTF-8') . ' ' .
                             '<a href="' . htmlspecialchars($fixedUrl, ENT_QUOTES, 'UTF-8') . '" ' .
                             'target="_blank" rel="noopener noreferrer" ' .
                             'class="kun_p2a_video_link" ' .
                             'data-tooltip="' . htmlspecialchars($tooltip, ENT_QUOTES, 'UTF-8') . '">' .
                             $icon . htmlspecialchars($displayText, ENT_QUOTES, 'UTF-8') .
                             '</a>';
                    
                    // Защищаем от повторной обработки
                    return '___PROCESSED_VIDEO_LINK___' . base64_encode($result) . '___END___';
                }
                
                // Обычная ссылка
                return '<a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '">' .
                       htmlspecialchars($linkText, ENT_QUOTES, 'UTF-8') . '</a>';
            },
            $text
        );
        
        // Затем обычная обработка видео-ссылок (не BBCode)
        $patterns = [
            'youtube' => [
                'pattern' => '#((?:https?://)?(?:www\.)?(?:youtube\.com/watch\?v=|youtu\.be/)([\w-]+)(?:[&\?]t=(\d+)s?)?[^\s]*)#i',
                'tag' => 'youtube',
                'iframe' => '<iframe width="560" height="315" src="https://www.youtube.com/embed/{VIDEO_ID}?start={TIME_PARAM}" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>'
            ],
            'vimeo' => [
                'pattern' => '#((?:https?://)?(?:www\.)?vimeo\.com/(\d+)[^\s]*)#i',
                'tag' => 'vimeo',
                'iframe' => '<iframe src="https://player.vimeo.com/video/{VIDEO_ID}" width="640" height="360" frameborder="0" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen></iframe>'
            ],
            'dailymotion' => [
                'pattern' => '#((?:https?://)?(?:www\.)?dailymotion\.com/video/([\w-]+)[^\s]*)#i',
                'tag' => 'dailymotion',
                'iframe' => null 
            ],
            'facebook' => [
                'pattern' => '#((?:https?://)?(?:www\.)?facebook\.com/(?:watch/?\?v=|.*?/videos/)(\d+)[^\s]*)#i',
                'tag' => 'facebook',
                'iframe' => null
            ],
            'soundcloud' => [
                'pattern' => '#((?:https?://)?(?:www\.)?soundcloud\.com/([\w-]+/[\w-]+(?:/[\w-]+)*)[^\s]*)#i',
                'tag' => 'soundcloud',
                'iframe' => null
            ]
        ];
        
        foreach ($patterns as $platform => $config) {
            $text = preg_replace_callback(
                $config['pattern'],
                function($matches) use ($platform, $config, $allVideosEnabled) {
                    $fullMatch = $matches[1];
                    
                    // Пропускаем уже обработанные ссылки
                    if (strpos($fullMatch, '___PROCESSED_VIDEO_LINK___') !== false) {
                        return $fullMatch;
                    }
                    
                    $videoId = $matches[2];
                    
                    $timeParam = '';
                    if ($platform === 'youtube' && isset($matches[3]) && !empty($matches[3])) {
                        $timeParam = $matches[3];
                    }
                    
                    if ($allVideosEnabled) {
                        if ($platform === 'facebook' || $platform === 'soundcloud') {
                            return '{' . $config['tag'] . '}' . $fullMatch . '{/' . $config['tag'] . '}';
                        }
                        return '{' . $config['tag'] . '}' . $videoId . '{/' . $config['tag'] . '}';
                    } else {
                        if ($config['iframe'] !== null) {
                            $iframe = str_replace('{VIDEO_ID}', $videoId, $config['iframe']);
                            
                            if ($platform === 'youtube' && !empty($timeParam)) {
                                $iframe = str_replace('?start={TIME_PARAM}', '?start=' . $timeParam, $iframe);
                            } else {
                                $iframe = str_replace('?start={TIME_PARAM}', '', $iframe);
                            }
                            
                            $marker = '___IFRAME_' . md5($iframe) . '___';
                            return $marker . '||' . base64_encode($iframe) . '||';
                        } else {
                            // Стилизованная ссылка
                            $fixedUrl = $this->fixVideoUrl($platform, $fullMatch);
                            $tooltip = Text::_('COM_KUNENATOPIC2ARTICLE_VIDEO_INSTALL_ALLVIDEOS');
                            $displayText = $this->getDisplayText($platform, $fixedUrl);
                            
                            $icon = ($platform === 'facebook') ? '<span class="facebook-icon">f</span>' : '';
                            
                            $result = '<a href="' . htmlspecialchars($fixedUrl, ENT_QUOTES, 'UTF-8') . '" ' .
                                     'target="_blank" rel="noopener noreferrer" ' .
                                     'class="kun_p2a_video_link" ' .
                                     'data-tooltip="' . htmlspecialchars($tooltip, ENT_QUOTES, 'UTF-8') . '">' .
                                     $icon . htmlspecialchars($displayText, ENT_QUOTES, 'UTF-8') .
                                     '</a>';
                            
                            return '___PROCESSED_VIDEO_LINK___' . base64_encode($result) . '___END___';
                        }
                    }
                },
                $text
            );
        }
        
        return $text;
    }
    
    /**
     * Check if AllVideos plugin is enabled
     */
    private function isAllVideosEnabled(): bool
    {
        try {
            $query = $this->db->getQuery(true)
                ->select('enabled')
                ->from('#__extensions')
                ->where('type = ' . $this->db->quote('plugin'))
                ->where('folder = ' . $this->db->quote('content'))
                ->where('element = ' . $this->db->quote('jw_allvideos'));
            
            $this->db->setQuery($query);
            $result = $this->db->loadResult();
            
            return (bool) $result;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Detect video platform from URL
     */
    private function detectVideoPlatform(string $url): ?string
    {
        $patterns = [
            'youtube' => '/youtube\.com|youtu\.be/',
            'vimeo' => '/vimeo\.com/',
            'dailymotion' => '/dailymotion\.com/',
            'facebook' => '/facebook\.com/',
            'soundcloud' => '/soundcloud\.com/'
        ];
        
        foreach ($patterns as $platform => $pattern) {
            if (preg_match($pattern, $url)) {
                return $platform;
            }
        }
        
        return null;
    }
    
    /**
     * Fix video URL for proper display
     */
    private function fixVideoUrl(string $platform, string $url): string
    {
        $url = trim($url);
        
        // Очистка
        $url = str_replace(["\xC2\xA0", "&nbsp;", "\n", "\r", "\t"], '', $url);
        $url = preg_replace('/a href=/i', '', $url);
        $url = preg_replace('/<\/?a[^>]*>/i', '', $url);
        $url = preg_replace('/^https?:\/\/s\/\//i', 'https://', $url);
        
        // Протокол
        if (!preg_match('/^https?:\/\//i', $url)) {
            $url = 'https://' . $url;
        }
        
        // Для Facebook гарантируем www.
        if ($platform === 'facebook' && strpos($url, 'www.facebook.com') === false) {
            $url = str_replace('facebook.com', 'www.facebook.com', $url);
        }
        
        // HTTPS
        $url = str_replace('http://', 'https://', $url);
        
        return $url;
    }
    
    /**
     * Get display text for video link
     */
    private function getDisplayText(string $platform, string $url): string
    {
        // Базовые названия платформ
        $platformNames = [
            'facebook' => 'Facebook',
            'youtube' => 'YouTube', 
            'vimeo' => 'Vimeo',
            'dailymotion' => 'Dailymotion',
            'soundcloud' => 'SoundCloud'
        ];
        
        $platformName = $platformNames[$platform] ?? 'Video';
        
        // Получаем часть URL для отображения
        $urlPart = preg_replace('/^https?:\/\//i', '', $url);
        $urlPart = preg_replace('/^www\./i', '', $urlPart);
        
        // Обрезаем если слишком длинный
        if (mb_strlen($urlPart) > 30) {
            $urlPart = mb_substr($urlPart, 0, 27) . '…';
        }
        
        // Формируем текст: "Платформа: сокращенный-URL"
        return $platformName . ': ' . $urlPart;
    }
    
    /**
     * Protect image tags from being processed as URLs
     */
    private function protectImageTags(string $text): string
    {
        static $protectedImages = [];
        
        return preg_replace_callback(
            '/\[img\](https?:\/\/[^\[]+?)\[\/img\]/i',
            function($matches) use (&$protectedImages) {
                $marker = '___IMGURL_' . count($protectedImages) . '___';
                $protectedImages[$marker] = $matches[0];
                return $marker;
            },
            $text
        );
    }
    
    /**
     * Restore protected image tags
     */
    private function restoreProtectedImages(string $text): string
    {
        static $protectedImages = [];
        
        // Найдем все защищенные изображения в тексте
        if (preg_match_all('/___IMGURL_(\d+)___/', $text, $matches)) {
            foreach ($matches[0] as $marker) {
                // Восстанавливаем оригинальный тег
                if (preg_match('/___IMGURL_(\d+)___/', $marker, $m)) {
                    $index = $m[1];
                    // Заменим маркер на оригинальный тег
                    $text = str_replace($marker, '[img]placeholder[/img]', $text);
                }
            }
        }
        
        return $text;
    }
    
    /**
     * Process regular URLs (not video)
     */
    private function processRegularUrls(string $text): string
    {
        return preg_replace_callback(
            '#(?<![\[="\'])(?<!href=)(https?://[^\s\[\]<>"\'\)]+)#i',
            function($m) {
                $url = rtrim($m[1], '.,;:!?');
                return '[url]' . $url . '[/url]';
            },
            $text
        );
    }
    
    /**
     * Process attachment tags
     */
    private function processAttachments(string $text): string
    {
        if (!$this->attachmentCallback) {
            return $text;
        }
        
        $attachments = [];
        
        return preg_replace_callback(
            '/\[attachment=(\d+)\](.*?)\[\/attachment\]/i', 
            function($matches) use (&$attachments) {
                $attachmentId = $matches[1];
                $filename = $matches[2];
                $marker = '###ATTACHMENT_' . count($attachments) . '###';
                $attachments[$marker] = [$attachmentId, $filename];
                return $marker;
            }, 
            $text
        );
    }
    
    /**
     * Post-process HTML after BBCode conversion
     */
    private function postProcessHtml(string $html): string
    {
        // Нормализуем br теги
        $html = preg_replace('/\s*<br\s*\/?>\s*/i', "\n", $html);
        
        // Восстанавливаем iframe
        $html = preg_replace_callback(
            '/___IFRAME_[a-f0-9]+___\|\|(.*?)\|\|/i',
            function($matches) {
                return base64_decode($matches[1]);
            },
            $html
        );
        
        // Восстанавливаем обработанные видео-ссылки
        $html = preg_replace_callback(
            '/___PROCESSED_VIDEO_LINK___(.*?)___END___/',
            function($matches) {
                return base64_decode($matches[1]);
            },
            $html
        );
        
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
        
        // Обертка iframe в контейнер
        if (strpos($html, '<iframe') !== false) {
            $html = preg_replace_callback(
                '/(<iframe[^>]*>.*?<\/iframe>)/is',
                function($matches) {
                    return '<div class="kun_p2a_video_container">' . $matches[1] . '</div>';
                },
                $html
            );
        }
        
        // Декодирование HTML-сущностей 
        $html = str_replace('&lt;', '<', $html);
        $html = str_replace('&gt;', '>', $html);
        $html = str_replace('&quot;', '"', $html);
        $html = str_replace('&amp;', '&', $html);
        
        return $html;
    }
    
    /**
     * Simple fallback BBCode conversion
     */
    private function simpleBBCodeToHtml(string $text): string
    {
        return 'NO PARSER';
    }
    
    /**
     * Process reminder lines (cut from HTML)
     */
    public function processReminderLines(string $htmlContent, int $reminderLinesLength): string
    {
        if ($reminderLinesLength <= 0) {
            return '';
        }

        mb_internal_encoding('UTF-8');
        
        $reminderLines = '';
        $link_symbol = '🔗';
        $image_symbol = '🖼️';

        // 1. Предварительная очистка
        $processedContent = preg_replace(
            '/(<p[^>]*>|<\/p>|<div[^>]*>|<\/div>|<span[^>]*>|<\/span>|<strong[^>]*>|<\/strong>|<em[^>]*>|<\/em>|<br\s*\/?>|&nbsp;|\s*[\r\n]+\s*)/iu',
            ' ',
            $htmlContent
        );
        $processedContent = html_entity_decode($processedContent, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $processedContent = trim($processedContent);

        // Регулярные выражения
        $combinedRegex = '~(<a\s+(?:[^>]*?\s+)?href=["\'](.*?)(?:["\'].*?)?>(.*?)<\/a>)|(<img\s+src=["\'](.*?)["\']\s+alt=["\'](.*?)["\']\s*\/?>)~iu';

        // 2. Итеративная обработка
        $lastOffset = 0;
        while (
            mb_strlen($reminderLines) < $reminderLinesLength
            && preg_match($combinedRegex, $processedContent, $matches, PREG_OFFSET_CAPTURE, $lastOffset)
        ) {
            $byteOffset = $matches[0][1];
            $byteLength = strlen($matches[0][0]);

            // 2a. Добавляем текст между последней обработанной позицией и текущим тегом
            $plainText = trim(mb_strcut($processedContent, $lastOffset, $byteOffset - $lastOffset, 'UTF-8'));
            $remainingSpaceForPlain = $reminderLinesLength - mb_strlen($reminderLines);
            $reminderLines .= mb_substr($plainText, 0, $remainingSpaceForPlain);
            
            if (mb_strlen($reminderLines) >= $reminderLinesLength) {
                $lastOffset = $byteOffset + $byteLength;
                break; 
            }

            if (mb_strlen($plainText) > 0 && mb_strlen($reminderLines) < $reminderLinesLength && mb_substr($reminderLines, -1) !== ' ') {
                $reminderLines .= ' ';
            }
            
            $replacement = '';
            
            $linkMatched = isset($matches[1]) && $matches[1][1] !== -1;
            $imageMatched = isset($matches[4]) && $matches[4][1] !== -1;
            
            // 2b. Формируем замену
            if ($linkMatched) {
                $href = $matches[2][0];
                $linkText = isset($matches[3]) && $matches[3][1] !== -1 ? $matches[3][0] : '';
                
                $linkTextCleaned = trim(strip_tags($linkText));
                
                if (!empty($linkTextCleaned) && strpos($linkTextCleaned, '://') === false && strpos($linkTextCleaned, 'www.') === false) {
                    $replacement = $link_symbol . $linkTextCleaned . $link_symbol;
                } else {
                    $sourceUrl = !empty($linkTextCleaned) ? $linkTextCleaned : $href;
                    $urlPart = preg_replace('#^https?://#i', '', $sourceUrl);
                    $urlPart = mb_strimwidth($urlPart, 0, 40, "...", 'UTF-8');
                    $replacement = $link_symbol . $urlPart . $link_symbol;
                }
            } elseif ($imageMatched) {
                $src = $matches[5][0];
                $alt = isset($matches[6]) && $matches[6][1] !== -1 ? $matches[6][0] : '';
                
                $replacementText = '';
                $altCleaned = trim(strip_tags($alt));
                
                if (!empty($altCleaned)) {
                    if (mb_substr($altCleaned, 0, 1) === '-') {
                        $replacementText = mb_substr($altCleaned, 1);
                    } else {
                        $replacementText = $altCleaned;
                    }
                }
                
                if (empty($replacementText)) {
                    $filename = basename($src);
                    $replacementText = urldecode($filename);
                }
                
                if (empty($replacementText)) {
                    $replacementText = 'рисунок'; 
                }
                
                $replacement = $image_symbol . $replacementText . $image_symbol;
            }

            // 2c. Вставляем элемент
            $remainingSpace = $reminderLinesLength - mb_strlen($reminderLines);
            
            if (mb_strlen($replacement) <= $remainingSpace) {
                $reminderLines .= $replacement;
                
                if (mb_strlen($reminderLines) < $reminderLinesLength && mb_substr($reminderLines, -1) !== ' ') {
                    $reminderLines .= ' ';
                }
            } else {
                $reminderLines .= $replacement;
                $lastOffset = $byteOffset + $byteLength;
                break; 
            }
            
            $lastOffset = $byteOffset + $byteLength;
        }

        // 3. Добавляем оставшийся текст
        $remainingText = trim(mb_strcut($processedContent, $lastOffset, null, 'UTF-8'));
        
        $max_append_length = $reminderLinesLength - mb_strlen($reminderLines);
        
        if (mb_strlen($remainingText) > 0 && $max_append_length > 0) {
            if (mb_strlen($reminderLines) > 0 && mb_substr($reminderLines, -1) !== ' ') {
                $reminderLines .= ' ';
                $max_append_length--; 
            }
            
            if ($max_append_length > 0) {
                $reminderLines .= mb_substr($remainingText, 0, $max_append_length);
            }
        }

        // 4. ФИНАЛЬНАЯ ОЧИСТКА
        $reminderLines = preg_replace('/\s{2,}/u', ' ', $reminderLines);
        $reminderLines = strip_tags($reminderLines);
        $reminderLines = trim($reminderLines);

        return $reminderLines;
    }
}
