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
use Joomla\CMS\Version;

/**
 * Video Processor Helper
 * Handles video links processing and AllVideos integration
 * 
 * @since  1.0.0
 */
class VideoProcessor
{
    private $db;
    private $app;
    
    public function __construct()
    {
        $this->app = Factory::getApplication();
        $this->db = Factory::getDbo();
        $this->loadComponentLanguage();
    }
    
    private function loadComponentLanguage(): void
    {
        static $loaded = false;
        
        if (!$loaded) {
            $lang = $this->app->getLanguage();
            $lang->load('com_kunenatopic2article', JPATH_ADMINISTRATOR);
            $lang->load('com_kunenatopic2article', JPATH_SITE);
            $loaded = true;
        }
    }
    
    /**
     * Main method: Process video links in text
     */
    public function processVideoLinks(string $text): string
    {
        $allVideosEnabled = $this->isAllVideosEnabled();
        
        // Сначала обрабатываем BBCode ссылки [url=...]текст[/url]
        $text = preg_replace_callback(
            '/\[url=([^\]]+)\](.*?)\[\/url\]/i',
            function($matches) use ($allVideosEnabled) {
                return $this->processUrlMatch($matches, $allVideosEnabled);
            },
            $text
        );

        // Затем обрабатываем BBCode ссылки вида [url]https://video...[/url].
        // Если оставить такой тег, следующий видео-проход вставит iframe внутрь href.
        $text = preg_replace_callback(
            '/\[url\](.*?)\[\/url\]/is',
            function($matches) {
                $url = trim($matches[1]);
                $url = trim($url, "\"'");

                if ($this->detectVideoPlatform($url)) {
                    return $url;
                }

                return $matches[0];
            },
            $text
        );
        
        // Затем обработка обычных URL (не в BBCode)
        $patterns = $this->getVideoPatterns();
        
        foreach ($patterns as $platform => $config) {
            $iterations = 0;
            $maxIterations = 100;
            
            while ($iterations < $maxIterations && preg_match($config['pattern'], $text)) {
                $beforeText = $text;
                
                $text = preg_replace_callback(
                    $config['pattern'],
                    function($matches) use ($platform, $config, $allVideosEnabled) {
                        return $this->processVideoMatch($matches, $platform, $config, $allVideosEnabled);
                    },
                    $text,
                    1
                );
                
                if ($beforeText === $text) {
                    break;
                }
                
                $iterations++;
            }
        }
             // если в тексте есть защищённые блоки ___PROTECTED___...___END___, вставляем <br> перед каждым вторым и далее подряд
            return $this->addBrBetweenConsecutiveVideos($text);
    }
    
    /**
 * Вставляем <br> между идущими подряд видео-блоками
 * (работает только на защищённых фрагментах ___PROTECTED___...___END___)
 */
private function addBrBetweenConsecutiveVideos(string $text): string
{
    // Ищем место МЕЖДУ концом одного блока и началом другого
    // (?<=...) - проверка назад: перед нами должен быть ___END___
    // \s* - любое количество пробелов/переносов
    // (?=...) - проверка вперед: после нас должен быть ___PROTECTED___
    return preg_replace('/(?<=___END___)\s*(?=___PROTECTED___)/i', '<br />', $text);
}
    
    public function isAllVideosEnabled(): bool
    {
        if (!$this->isAllVideosSupportedByJoomlaVersion()) {
            return false;
        }

        return $this->isAllVideosPluginEnabled();
    }

    public function isAllVideosSupportedByJoomlaVersion(): bool
    {
        $version = new Version();

        return version_compare($version->getShortVersion(), '6.0', '<');
    }

    public function isAllVideosPluginEnabled(): bool
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
    
    private function getVideoPatterns(): array
    {
        return [
           'youtube' => [
    'pattern' => '#(?<!___PROTECTED___)(?<![{\[])(?:https?://)?(?:www\.|m\.)?(?:youtube\.com/watch\?v=|youtu\.be/)([\w-]+)(?:[?&]t=(?:(?:(\d+)h)?(?:(\d+)m)?(?:(\d+)s?)|(\d+)))?(?:[^\s\n\r\t<>"]*)?(?=\s|$|[^\w&?=-])#i',
    'tag' => 'youtube',
    'iframe' => '<div class="kun_p2a_video_container"><iframe width="560" height="315" src="https://www.youtube.com/embed/{VIDEO_ID}?start={TIME_PARAM}" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe></div>'
],
            'vimeo' => [
                // Поддержка player.vimeo.com
                'pattern' => '#(?<!___PROTECTED___)(?<![{\[])(?:https?://)?(?:(?:www\.|player\.)?vimeo\.com/(?:video/)?(\d+))(?=\s|$|[^\w/-])#i',
                'tag' => 'vimeo',
                'iframe' => '<div class="kun_p2a_video_container"><iframe src="https://player.vimeo.com/video/{VIDEO_ID}" width="640" height="360" frameborder="0" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen></iframe></div>'
            ],
            'dailymotion' => [
                // Поддержка dai.ly и старого формата с _
                'pattern' => '#(?<!___PROTECTED___)(?<![{\[])(?:https?://)?(?:(?:www\.)?dailymotion\.com/video/|dai\.ly/)([\w-]+)(?:_[^\s]*)?(?=\s|$|[^\w-])#i',
                'tag' => 'dailymotion',
                'iframe' => null 
            ],
            'facebook' => [
               // Поддержка m.facebook.com и fb.watch, захват ID
                'pattern' => '#(?<!___PROTECTED___)(?<![{\[])(?:https?://)?(?:(?:www\.|m\.)?facebook\.com/(?:watch/?\?v=|video\.php\?v=|.*?/videos/)|fb\.watch/)([\w-]+)(?:/)?(?=\s|$|[^\w/-])#i',
                 'tag' => 'facebook',
                'iframe' => null
            ],
            'soundcloud' => [
                // Улучшенный паттерн для SoundCloud (без обрезки параметров)
                'pattern' => '#(?<!___PROTECTED___)(?<![{\[])(?:https?://)?(?:www\.)?soundcloud\.com/([\w-]+/[\w-]+(?:/[\w-]+)*(?:\?[^\s]*)?)(?=\s|$)#i',
                'tag' => 'soundcloud',
                'iframe' => null
            ]
        ];
    }
    
    private function processUrlMatch(array $matches, bool $allVideosEnabled): string
    {
        $url = trim($matches[1]);
        $url = trim($url, "\"'");
        $linkText = trim($matches[2]);
        
        $platform = $this->detectVideoPlatform($url);
        
        if ($platform) {
            $fixedUrl = $this->fixVideoUrl($platform, $url);
            
            if ($allVideosEnabled) {
                // Для Facebook: всегда ссылка, даже с AllVideos
                if ($platform === 'facebook') {
                    return $this->createStyledVideoLink($platform, $fixedUrl, $linkText);
                }
                
                // Для AllVideos: возвращаем ТОЛЬКО тег, БЕЗ текста ссылки
                $config = $this->getVideoPatterns()[$platform];
                
                if (preg_match($config['pattern'], $fixedUrl, $urlMatches)) {
                    $videoId = $urlMatches[1] ?? '';
                    
                    if ($platform === 'soundcloud') {
                        $tag = '{' . $config['tag'] . '}' . $fixedUrl . '{/' . $config['tag'] . '}';
                    } else {
                        $tag = '{' . $config['tag'] . '}' . $videoId . '{/' . $config['tag'] . '}';
                    }
                    
                    return '___PROTECTED___' . base64_encode($tag) . '___END___';
                }
            }
            
            // Без AllVideos: текст + кнопка
            return $this->createStyledVideoLink($platform, $fixedUrl, $linkText);
        }
        
        // Обычные ссылки оставляем штатному BBCode-парсеру.
        return '[url="' . $url . '"]' . $linkText . '[/url]';
    }
    
   private function processVideoMatch(array $matches, string $platform, array $config, bool $allVideosEnabled): string
{
    $fullMatch = $matches[0];
    $videoId = $matches[1];
    $videoUrl = $this->fixVideoUrl($platform, $fullMatch);

    $timeParam = 0;
    if ($platform === 'youtube') {
        // Проверяем, захвачены ли группы времени
        // $matches[2] - часы, [3] - минуты, [4] - секунды, [5] - просто число секунд (например, t=90)
        if (!empty($matches[5])) {
            $timeParam = (int)$matches[5];
        } else {
            $hours   = !empty($matches[2]) ? (int)$matches[2] : 0;
            $minutes = !empty($matches[3]) ? (int)$matches[3] : 0;
            $seconds = !empty($matches[4]) ? (int)$matches[4] : 0;
            
            $timeParam = ($hours * 3600) + ($minutes * 60) + $seconds;
        }
    }

    // Если время найдено (больше 0), мы ВСЕГДА используем наш iframe, 
    // так как AllVideos плохо дружит с метками времени.
    if ($timeParam > 0) {
        $iframe = str_replace(['{VIDEO_ID}', '{TIME_PARAM}'], [$videoId, $timeParam], $config['iframe']);
        return '___PROTECTED___' . base64_encode($iframe) . '___END___';
    }
    
    if ($allVideosEnabled) {
        // ОСОБЫЕ СЛУЧАИ: Не используем AllVideos
        
        // 1. Facebook - всегда красивая ссылка
        if ($platform === 'facebook') {
            $fixedUrl = $this->fixVideoUrl($platform, $fullMatch);
            return $this->createStyledVideoLink($platform, $fixedUrl);
        }
        
        // 2. YouTube с временной меткой - создаем свой iframe
        if ($platform === 'youtube' && !empty($timeParam)) {
            $iframe = str_replace('{VIDEO_ID}', $videoId, $config['iframe']);
            $iframe = str_replace('?start={TIME_PARAM}', '?start=' . $timeParam, $iframe);
            
            return '___PROTECTED___' . base64_encode($iframe) . '___END___';
        }
        
        // ОБЫЧНЫЕ СЛУЧАИ: Используем AllVideos
        if ($platform === 'soundcloud') {
            $fixedUrl = $this->fixVideoUrl($platform, $fullMatch);
            $tag = '{' . $config['tag'] . '}' . $fixedUrl . '{/' . $config['tag'] . '}';
        } else {
            $tag = '{' . $config['tag'] . '}' . $videoId . '{/' . $config['tag'] . '}';
        }
        
        return '___PROTECTED___' . base64_encode($tag) . '___END___';
        
    } else {
        // Без AllVideos
        if ($config['iframe'] !== null) {
            $iframe = str_replace('{VIDEO_ID}', $videoId, $config['iframe']);
            
            if ($platform === 'youtube' && !empty($timeParam)) {
                $iframe = str_replace('?start={TIME_PARAM}', '?start=' . $timeParam, $iframe);
            } else {
                $iframe = str_replace('?start={TIME_PARAM}', '', $iframe);
            }
            
            return '___PROTECTED___' . base64_encode($iframe) . '___END___';
            
        } else {
            $fixedUrl = $this->fixVideoUrl($platform, $fullMatch);
            return $this->createStyledVideoLink($platform, $fixedUrl);
        }
    }
}
    
    /**
     * Create styled video link with icon
     */
    private function createStyledVideoLink(string $platform, string $url, string $prefix = ''): string
    {
        if ($platform === 'facebook') {
            $tooltip = Text::_('COM_KUNENATOPIC2ARTICLE_VIDEO_FACEBOOK_NOTICE');
        } else {
            $tooltip = Text::_('COM_KUNENATOPIC2ARTICLE_VIDEO_INSTALL_ALLVIDEOS');
        }
        
        $displayText = $this->getDisplayText($platform, $url);
        
        // Иконка видео для всех платформ
        $icon = '<span class="video-icon">📹</span>';
        
        $result = '';
        if (!empty($prefix)) {
            $result = htmlspecialchars($prefix, ENT_QUOTES, 'UTF-8') . ' ';
        }
        
        $result .= '<a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" ' .
                  'target="_blank" rel="noopener noreferrer" ' .
                  'class="kun_p2a_video_link" ' .
                  'data-tooltip="' . htmlspecialchars($tooltip, ENT_QUOTES, 'UTF-8') . '">' .
                  $icon . htmlspecialchars($displayText, ENT_QUOTES, 'UTF-8') .
                  '</a>';
        
        return '___PROTECTED___' . base64_encode($result) . '___END___';
    }
    
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
    
    private function fixVideoUrl(string $platform, string $url): string
    {
        $url = trim($url);
        $url = str_replace(["\xC2\xA0", "&nbsp;", "\n", "\r", "\t"], '', $url);
        $url = preg_replace('/a href=/i', '', $url);
        $url = preg_replace('/<\/?a[^>]*>/i', '', $url);
        $url = preg_replace('/^https?:\/\/s\/\//i', 'https://', $url);
        
        // Протокол
        if (!preg_match('/^https?:\/\//i', $url)) {
            $url = 'https://' . $url;
        }
        
        // Нормализация мобильных версий
        $url = str_replace('m.youtube.com', 'www.youtube.com', $url);
        $url = str_replace('m.facebook.com', 'www.facebook.com', $url);
        
        // Нормализация коротких ссылок
        if ($platform === 'dailymotion' && strpos($url, 'dai.ly') !== false) {
            // dai.ly/x8abcde → dailymotion.com/video/x8abcde
            $url = preg_replace('#dai\.ly/([a-z0-9]+)#i', 'www.dailymotion.com/video/$1', $url);
        }
        
        if ($platform === 'facebook' && strpos($url, 'fb.watch') !== false) {
            // fb.watch остается как есть - Facebook сам редиректит, но проверяенм, что есть протокол
              // Нормализация для Facebook: /watch/?v= -> /video.php?v= - не работает (проверено!)
            if (!preg_match('/^https?:\/\//i', $url)) {
                $url = 'https://' . $url;
            }
        }
                
        if ($platform === 'vimeo' && strpos($url, 'player.vimeo.com') !== false) {
            // player.vimeo.com/video/123 → vimeo.com/123
            $url = preg_replace('#player\.vimeo\.com/video/(\d+)#i', 'vimeo.com/$1', $url);
        }
        
        // Для Facebook гарантируем www.
        if ($platform === 'facebook' && strpos($url, 'fb.watch') === false && strpos($url, 'www.facebook.com') === false) {
            $url = str_replace('facebook.com', 'www.facebook.com', $url);
        }
        
        // HTTPS
        $url = str_replace('http://', 'https://', $url);
        
        // Убираем завершающий слеш для Facebook
        if ($platform === 'facebook') {
            $url = rtrim($url, '/');
        }
        
        return $url;
    }
    
    private function getDisplayText(string $platform, string $url): string
    {
        $platformNames = [
            'facebook' => 'Facebook',
            'youtube' => 'YouTube', 
            'vimeo' => 'Vimeo',
            'dailymotion' => 'Dailymotion',
            'soundcloud' => 'SoundCloud'
        ];
        
        $platformName = $platformNames[$platform] ?? 'Video';
        $urlPart = preg_replace('/^https?:\/\//i', '', $url);
        $urlPart = preg_replace('/^www\./i', '', $urlPart);
        
        if (mb_strlen($urlPart) > 30) {
            $urlPart = mb_substr($urlPart, 0, 27) . '…';
        }
        
        return $platformName . ': ' . $urlPart;
    }
    
    public function extractVideoFromBBCode(string $text): string
    {
        return preg_replace('/\[video\](https?:\/\/[^\[]+?)\[\/video\]/i', '$1', $text);
    }
    
    /**
     * Remove AllVideos tags from text (for reminder lines)
     * Replaces any tags in format {tag}...{/tag} with video symbol
     *
     * @param   string  $text  Text with potential AllVideos tags
     * 
     * @return  string  Text with tags replaced by video symbol
     */
    public function removeAllVideosTags(string $text): string
    {
        $videoLabel = Text::_('COM_KUNENATOPIC2ARTICLE_VIDEO_LABEL');
        return preg_replace('/\{[a-z0-9_-]+\}.*?\{\/[a-z0-9_-]+\}/is', '📹' . $videoLabel . '📹', $text);
    }
}
