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

use Joomla\CMS\Language\Text;

class ArticleContentRenderer
{
    public function renderOpeningContent(?string $infoText = null, ?string $warningText = null): string
    {
        return ($infoText ?? Text::_('COM_KUNENATOPIC2ARTICLE_INFORMATION_SIGN')) . '<br />'
            . ($warningText ?? Text::_('COM_KUNENATOPIC2ARTICLE_WARNING_SIGN'))
            . '<div class="kun_p2a_divider-shadow"></div>';
    }

    public function embedCss(string $content): string
    {
        $cssPath = JPATH_SITE . '/media/com_kunenatopic2article/css/kun_p2a.css';
        $cssContent = file_get_contents($cssPath);
        $cssStyle = '<style>' . PHP_EOL . $cssContent . PHP_EOL . '</style>' . PHP_EOL;

        return $cssStyle . $content;
    }
}
