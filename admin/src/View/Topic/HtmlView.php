<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_kunenatopic2article
 *
 * @copyright   (C) 2025 Leonid Ratner. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\KunenaTopic2Article\Administrator\View\Topic;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\Component\KunenaTopic2Article\Administrator\Helper\VideoProcessor;

class HtmlView extends BaseHtmlView
{
    protected $state;
    protected $form;
    protected $params;
    protected $canCreate;

    public function display($tpl = null)
    {
        
        // Получаем данные из модели
        $this->form = $this->get('Form');
        $this->state = $this->get('State');
        
        // Получаем модель для дополнительных данных
        $model = $this->getModel();
        if ($model) {
            $this->params = $model->getTableParams();
            $this->canCreate = Factory::getApplication()->getUserState('com_kunenatopic2article.can_create', false);
        }

        // Проверяем на ошибки
        if (count($errors = $this->get('Errors'))) {
            Factory::getApplication()->enqueueMessage(implode("\n", $errors), 'error');
            return false;
        }

        // Проверяем форму
        if (!$this->form) {
            Factory::getApplication()->enqueueMessage(Text::_('COM_KUNENATOPIC2ARTICLE_FORM_FAILED_TO_LOAD'), 'error');
        }

        // Проверяем состояние AllVideos 
$videoHelper = new VideoProcessor();
if (!$videoHelper->isAllVideosEnabled()) {
    Factory::getApplication()->enqueueMessage(
        Text::_('COM_KUNENATOPIC2ARTICLE_WARNING_ALLVIDEOS_MISSING'), 
        'warning'
    );
}
        
        parent::display($tpl);
    }
}
