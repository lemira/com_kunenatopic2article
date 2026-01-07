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
        
        // Затем обработка обычных URL (не в BBCode)
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
    
    private function getVideoPatterns(): array
    {
        return [
            'youtube' => [
                'pattern' => '#(?<!___PROTECTED___)(?<![{\[])(?:https?://)?(?:www\.)?(?:youtube\.com/watch\?v=|youtu\.be/)([\w-]+)(?:[&\?]t=(\d+)s?)?(?![}\]])#i',
                'tag' => 'youtube',
                'iframe' => '<div class="kun_p2a_video_container"><iframe width="560" height="315" src="https://www.youtube.com/embed/{VIDEO_ID}?start={TIME_PARAM}" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe></div>'
            ],
            'vimeo' => [
                'pattern' => '#(?<!___PROTECTED___)(?<![{\[])(?:https?://)?(?:www\.)?vimeo\.com/(\d+)(?![}\]])#i',
                'tag' => 'vimeo',
                'iframe' => '<div class="kun_p2a_video_container"><iframe src="https://player.vimeo.com/video/{VIDEO_ID}" width="640" height="360" frameborder="0" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen></iframe></div>'
            ],
            'dailymotion' => [
                'pattern' => '#(?<!___PROTECTED___)(?<![{\[])(?:https?://)?(?:www\.)?dailymotion\.com/video/([\w-]+)(?![}\]])#i',
                'tag' => 'dailymotion',
                'iframe' => null 
            ],
            'facebook' => [
                'pattern' => '#(?<!___PROTECTED___)(?<![{\[])(?:https?://)?(?:www\.)?facebook\.com/(?:watch/?\?v=|.*?/videos/)(\d+)(?![}\]])#i',
                'tag' => 'facebook',
                'iframe' => null
            ],
            'soundcloud' => [
                'pattern' => '#(?<!___PROTECTED___)(?<![{\[])(?:https?://)?(?:www\.)?soundcloud\.com/([\w-]+/[\w-]+(?:/[\w-]+)*)(?![}\]])#i',
                'tag' => 'soundcloud',
                'iframe' => null
            ]
        ];
    }
    
    private function processUrlMatch(array $matches, bool $allVideosEnabled): string
    {
        $url = trim($matches[1]);
        $linkText = trim($matches[2]);
        
        $platform = $this->detectVideoPlatform($url);
        
        if ($platform) {
            $fixedUrl = $this->fixVideoUrl($platform, $url);
            
            if ($allVideosEnabled) {
                // Для AllVideos: возвращаем ТОЛЬКО тег, БЕЗ текста ссылки
                $config = $this->getVideoPatterns()[$platform];
                
                if (preg_match($config['pattern'], $fixedUrl, $urlMatches)) {
                    $videoId = $urlMatches[1] ?? '';
                    
                    if ($platform === 'facebook' || $platform === 'soundcloud') {
                        $tag = '{' . $config['tag'] . '}' . $fixedUrl . '{/' . $config['tag'] . '}';
                    } else {
                        $tag = '{' . $config['tag'] . '}' . $videoId . '{/' . $config['tag'] . '}';
                    }
                    
                    return '___PROTECTED___' . base64_encode($tag) . '___END___';
                }
            }
            
            // Без AllVideos: текст + кнопка
            $tooltip = Text::_('COM_KUNENATOPIC2ARTICLE_VIDEO_INSTALL_ALLVIDEOS');
            $displayText = $this->getDisplayText($platform, $fixedUrl);
            $icon = ($platform === 'facebook') ? '<span class="facebook-icon">f</span>' : '';
            
            $result = htmlspecialchars($linkText, ENT_QUOTES, 'UTF-8') . ' ' .
                     '<a href="' . htmlspecialchars($fixedUrl, ENT_QUOTES, 'UTF-8') . '" ' .
                     'target="_blank" rel="noopener noreferrer" ' .
                     'class="kun_p2a_video_link" ' .
                     'data-tooltip="' . htmlspecialchars($tooltip, ENT_QUOTES, 'UTF-8') . '">' .
                     $icon . htmlspecialchars($displayText, ENT_QUOTES, 'UTF-8') .
                     '</a>';
            
            return '___PROTECTED___' . base64_encode($result) . '___END___';
        }
        
        // Обычная ссылка
        return '<a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '">' .
               htmlspecialchars($linkText, ENT_QUOTES, 'UTF-8') . '</a>';
    }
    
    private function processVideoMatch(array $matches, string $platform, array $config, bool $allVideosEnabled): string
    {
        $fullMatch = $matches[0];
        $videoId = $matches[1];
        
        $timeParam = '';
        if ($platform === 'youtube' && isset($matches[2]) && !empty($matches[2])) {
            $timeParam = $matches[2];
        }
        
        if ($allVideosEnabled) {
            if ($platform === 'facebook' || $platform === 'soundcloud') {
                $fixedUrl = $this->fixVideoUrl($platform, $fullMatch);
                $tag = '{' . $config['tag'] . '}' . $fixedUrl . '{/' . $config['tag'] . '}';
            } else {
                $tag = '{' . $config['tag'] . '}' . $videoId . '{/' . $config['tag'] . '}';
            }
            
            return '___PROTECTED___' . base64_encode($tag) . '___END___';
            
        } else {
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
                $tooltip = Text::_('COM_KUNENATOPIC2ARTICLE_VIDEO_INSTALL_ALLVIDEOS');
                $displayText = $this->getDisplayText($platform, $fixedUrl);
                $icon = ($platform === 'facebook') ? '<span class="facebook-icon">f</span>' : '';
                
                $result = '<a href="' . htmlspecialchars($fixedUrl, ENT_QUOTES, 'UTF-8') . '" ' .
                         'target="_blank" rel="noopener noreferrer" ' .
                         'class="kun_p2a_video_link" ' .
                         'data-tooltip="' . htmlspecialchars($tooltip, ENT_QUOTES, 'UTF-8') . '">' .
                         $icon . htmlspecialchars($displayText, ENT_QUOTES, 'UTF-8') .
                         '</a>';
                
                return '___PROTECTED___' . base64_encode($result) . '___END___';
            }
        }
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
        
        if (!preg_match('/^https?:\/\//i', $url)) {
            $url = 'https://' . $url;
        }
        
        if ($platform === 'facebook' && strpos($url, 'www.facebook.com') === false) {
            $url = str_replace('facebook.com', 'www.facebook.com', $url);
        }
        
        $url = str_replace('http://', 'https://', $url);
        
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
}
