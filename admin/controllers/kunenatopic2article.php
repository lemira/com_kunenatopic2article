<?php
/**
 * @package    ComKunenaTopic2Article
 * @author     Alexey Baskinov, Jorn Wildt, Richard Fath  
 * @copyright  Copyright (c) 2009 - 2024 Joomla! Vargas. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\AdminController;

/**
 * KunenaTopic2Article Base Controller
 *
 * @package     Joomla.Administrator
 * @subpackage  com_kunenatopic2article
 * @since       1.6
 */
class KunenaTopic2ArticleController extends AdminController
{
    /**
     * The default view.
     *
     * @var    string
     * @since  1.6
     */
    protected $default_view = 'articles';

    /**
     * Method to display a view.
     *
     * @param   boolean  $cachable   If true, the view output will be cached
     * @param   array    $urlparams  An array of safe URL parameters and their variable types, for valid values see {@link JFilterInput::clean()}.
     *
     * @return  BaseController|bool  This object to support chaining.
     *
     * @since   1.5
     */
    public function display($cachable = false, $urlparams = array())
    {
        return parent::display($cachable, $urlparams);
    }
}
