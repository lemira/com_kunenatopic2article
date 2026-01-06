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

/**
 * Video Processor Helper
 * Handles video links processing and AllVideos integration
 * 
 * @since  1.0.0
 */
class VideoProcessor
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
     * Main method: Process video links in text
     *
     * @param   string  $text  The text to process
     * 
     * @return  string  Processed text
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
        
        // Затем обычная обработка видео-ссылок (не BBCode)
        $patterns = $this->getVideoPatterns();
        
        foreach ($patterns as $platform => $config) {
            $text = preg_replace_callback(
                $config['pattern'],
                function($matches) use ($platform, $config, $allVideosEnabled) {
                    return $this->processVideoMatch($matches, $platform, $config, $allVideosEnabled);
                },
                $text
            );
        }
        
        return $text;
    }
    
    /**
     * Check if AllVideos plugin is enabled
     *
     * @return  boolean
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
     * Get video patterns configuration
     *
     * @return  array
     */
    private function getVideoPatterns(): array
    {
        return [
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
    }
    
    /**
     * Process URL match from BBCode
     *
     * @param   array   $matches            Regex matches
     * @param   bool    $allVideosEnabled   Whether AllVideos is enabled
     * 
     * @return  string  Processed result
     */
    private function processUrlMatch(array $matches, bool $allVideosEnabled): string
    {
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
    }
    
    /**
     * Process video match from URL
     *
     * @param   array   $matches            Regex matches
     * @param   string  $platform           Video platform
     * @param   array   $config             Platform configuration
     * @param   bool    $allVideosEnabled   Whether AllVideos is enabled
     * 
     * @return  string  Processed result
     */
    private function processVideoMatch(array $matches, string $platform, array $config, bool $allVideosEnabled): string
    {
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
    }
    
    /**
     * Detect video platform from URL
     *
     * @param   string  $url  The URL to check
     * 
     * @return  string|null  Platform name or null
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
     *
     * @param   string  $platform  Video platform
     * @param   string  $url       Original URL
     * 
     * @return  string  Fixed URL
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
     *
     * @param   string  $platform  Video platform
     * @param   string  $url       Video URL
     * 
     * @return  string  Display text
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
     * Extract video from BBCode [video] tags
     *
     * @param   string  $text  Text containing BBCode
     * 
     * @return  string  Text with extracted video URLs
     */
    public function extractVideoFromBBCode(string $text): string
    {
        return preg_replace('/\[video\](https?:\/\/[^\[]+?)\[\/video\]/i', '$1', $text);
    }
}
