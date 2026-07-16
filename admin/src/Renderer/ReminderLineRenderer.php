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

use Joomla\Component\KunenaTopic2Article\Administrator\Helper\VideoProcessor;

class ReminderLineRenderer
{
    public function __construct(private VideoProcessor $videoProcessor)
    {
    }

    public function render(string $htmlContent, int $reminderLinesLength): string
    {
        if ($reminderLinesLength <= 0) {
            return '';
        }

        mb_internal_encoding('UTF-8');

        $htmlContent = $this->videoProcessor->removeAllVideosTags($htmlContent);
        $reminderLines = '';
        $linkSymbol = '🔗';
        $imageSymbol = '🖼️';

        $processedContent = preg_replace(
            '/(<p[^>]*>|<\/p>|<div[^>]*>|<\/div>|<span[^>]*>|<\/span>|<strong[^>]*>|<\/strong>|<em[^>]*>|<\/em>|<br\s*\/?>|&nbsp;|\s*[\r\n]+\s*)/iu',
            ' ',
            $htmlContent
        );
        $processedContent = html_entity_decode($processedContent, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $processedContent = trim($processedContent);

        $combinedRegex = '~(<a\s+(?:[^>]*?\s+)?href=["\'](.*?)(?:["\'].*?)?>(.*?)<\/a>)|(<img\s+src=["\'](.*?)["\']\s+alt=["\'](.*?)["\']\s*\/?>)~iu';

        $lastOffset = 0;
        while (
            mb_strlen($reminderLines) < $reminderLinesLength
            && preg_match($combinedRegex, $processedContent, $matches, PREG_OFFSET_CAPTURE, $lastOffset)
        ) {
            $byteOffset = $matches[0][1];
            $byteLength = strlen($matches[0][0]);

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

            $replacement = $this->buildReplacement($matches, $linkSymbol, $imageSymbol);
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

        $reminderLines = $this->appendRemainingText($reminderLines, $processedContent, $lastOffset, $reminderLinesLength);
        $reminderLines = preg_replace('/\s{2,}/u', ' ', $reminderLines);
        $reminderLines = strip_tags($reminderLines);

        return trim($reminderLines);
    }

    private function buildReplacement(array $matches, string $linkSymbol, string $imageSymbol): string
    {
        $linkMatched = isset($matches[1]) && $matches[1][1] !== -1;
        $imageMatched = isset($matches[4]) && $matches[4][1] !== -1;

        if ($linkMatched) {
            return $this->buildLinkReplacement($matches, $linkSymbol);
        }

        if ($imageMatched) {
            return $this->buildImageReplacement($matches, $imageSymbol);
        }

        return '';
    }

    private function buildLinkReplacement(array $matches, string $linkSymbol): string
    {
        $href = $matches[2][0];
        $linkText = isset($matches[3]) && $matches[3][1] !== -1 ? $matches[3][0] : '';
        $linkTextCleaned = trim(strip_tags($linkText));

        if (!empty($linkTextCleaned) && strpos($linkTextCleaned, '://') === false && strpos($linkTextCleaned, 'www.') === false) {
            return $linkSymbol . $linkTextCleaned . $linkSymbol;
        }

        $sourceUrl = !empty($linkTextCleaned) ? $linkTextCleaned : $href;
        $urlPart = preg_replace('#^https?://#i', '', $sourceUrl);
        $urlPart = mb_strimwidth($urlPart, 0, 40, "...", 'UTF-8');

        return $linkSymbol . $urlPart . $linkSymbol;
    }

    private function buildImageReplacement(array $matches, string $imageSymbol): string
    {
        $src = $matches[5][0];
        $alt = isset($matches[6]) && $matches[6][1] !== -1 ? $matches[6][0] : '';
        $replacementText = '';
        $altCleaned = trim(strip_tags($alt));

        if (!empty($altCleaned)) {
            $replacementText = mb_substr($altCleaned, 0, 1) === '-' ? mb_substr($altCleaned, 1) : $altCleaned;
        }

        if (empty($replacementText)) {
            $replacementText = urldecode(basename($src));
        }

        if (empty($replacementText)) {
            $replacementText = 'рисунок';
        }

        return $imageSymbol . $replacementText . $imageSymbol;
    }

    private function appendRemainingText(string $reminderLines, string $processedContent, int $lastOffset, int $reminderLinesLength): string
    {
        $remainingText = trim(mb_strcut($processedContent, $lastOffset, null, 'UTF-8'));
        $maxAppendLength = $reminderLinesLength - mb_strlen($reminderLines);

        if (mb_strlen($remainingText) > 0 && $maxAppendLength > 0) {
            if (mb_strlen($reminderLines) > 0 && mb_substr($reminderLines, -1) !== ' ') {
                $reminderLines .= ' ';
                $maxAppendLength--;
            }

            if ($maxAppendLength > 0) {
                $reminderLines .= mb_substr($remainingText, 0, $maxAppendLength);
            }
        }

        return $reminderLines;
    }
}
