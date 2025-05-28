<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_kunenatopic2article
 *
 * @copyright   Copyright (C) 2023 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Factory;

/**
 * Main Controller for KunenaTopic2Article component
 *
 * @since  0.0.1
 */
class KunenaTopic2ArticleController extends BaseController
{
    /**
     * The default view for the display method
     *
     * @var    string
     * @since  0.0.1
     */
    protected $default_view = 'topic';

    /**
     * Method to display a view
     *
     * @param   boolean  $cachable   If true, the view output will be cached
     * @param   array    $urlparams  An array of safe url parameters and their variable types
     *
     * @return  static  This object to support chaining
     *
     * @since   0.0.1
     */
    public function display($cachable = false, $urlparams = [])
    {
        $document = Factory::getApplication()->getDocument();
        $input = Factory::getApplication()->input;

        $vName = $input->get('view', $this->default_view);
        $vFormat = $document->getType();
        $lName = $input->get('layout', 'default');
        
        // Get and render the view.
        if ($view = $this->getView($vName, $vFormat)) {
            // Get the model for the view.
            $model = $this->getModel($vName);
            
            // Push the model into the view (as default).
            if ($model) {
                $view->setModel($model, true);
            }
            $view->setLayout($lName);
            // Push document object into the view.
            $view->document = $document;
            $view->display();
        }
        return $this;
    }

    /**
     * Method to get a model object, loading it if required
     *
     * @param   string  $name    The model name. Optional.
     * @param   string  $prefix  The class prefix. Optional.
     * @param   array   $config  Configuration array for model. Optional.
     *
     * @return  \Joomla\CMS\MVC\Model\BaseDatabaseModel|boolean  Model object on success; otherwise false on failure.
     *
     * @since   0.0.1
     */
    public function getModel($name = '', $prefix = 'KunenaTopic2ArticleModel', $config = [])
    {
        if (empty($name)) {
            $name = $this->input->get('view', $this->default_view);
        }

        return parent::getModel($name, $prefix, $config);
    }
}
