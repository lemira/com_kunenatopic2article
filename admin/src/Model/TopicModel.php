<?php
/**
 * @package     Kunena Topic to Article
 * @subpackage  com_kunenatopic2article
 * @version     1.0.0
 * @copyright   Copyright (C) 2025 lr. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

namespace Lr\Component\Kunenatopic2article\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Table\Table;

/**
 * Topic model for Kunena Topic to Article component
 * Handles form parameters and database operations
 */
class TopicModel extends AdminModel
{
    /**
     * The type alias for this content type
     *
     * @var    string
     * @since  1.0.0
     */
    public $typeAlias = 'com_kunenatopic2article.topic';

    /**
     * Flag indicating if parameters are remembered in database
     *
     * @var    boolean
     * @since  1.0.0
     */
    protected $paramsRemembered = false;

    /**
     * Method to get the record form.
     *
     * @param   array    $data      Data for the form.
     * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
     *
     * @return  Form|boolean  A Form object on success, false on failure
     *
     * @since   1.0.0
     */
    public function getForm($data = array(), $loadData = true)
    {
        // Get the form.
        $form = $this->loadForm('com_kunenatopic2article.topic', 'topic', array('control' => 'jform', 'load_data' => $loadData));

        if (empty($form)) {
            return false;
        }

        return $form;
    }

    /**
     * Method to get the data that should be injected in the form.
     *
     * @return  array  The data for the form.
     *
     * @since   1.0.0
     */
    protected function loadFormData()
    {
        // Check the session for previously entered form data.
        $app = Factory::getApplication();
        $data = $app->getUserState('com_kunenatopic2article.edit.topic.data', array());

        if (empty($data)) {
            // Load saved parameters from database
            $data = $this->loadSavedParameters();
        }

        return $data;
    }

    /**
     * Load saved parameters from kunenatopic2article_params table
     *
     * @return  array  The saved parameters
     *
     * @since   1.0.0
     */
    protected function loadSavedParameters()
    {
        try {
            $db = $this->getDatabase();
            $query = $db->getQuery(true);
            
            $query->select('*')
                  ->from('#__kunenatopic2article_params')
                  ->order('id DESC');
            
            $db->setQuery($query, 0, 1);
            $result = $db->loadObject();
            
            if ($result) {
                $this->paramsRemembered = true;
                
                return array(
                    'topic_id' => $result->topic_id,
                    'article_category' => $result->article_category,
                    'max_article_size' => $result->max_article_size,
                    'post_transfer_scheme' => $result->post_transfer_scheme,
                    'ignored_authors' => $result->ignored_authors,
                    'reminder_lines' => $result->reminder_lines,
                    'post_author' => $result->post_author,
                    'post_creation_date' => $result->post_creation_date,
                    'post_creation_time' => $result->post_creation_time,
                    'post_ids' => $result->post_ids,
                    'post_title' => $result->post_title,
                    'kunena_post_link' => $result->kunena_post_link
                );
            }
            
        } catch (\Exception $e) {
            // Silent fail, return empty array
        }
        
        $this->paramsRemembered = false;
        return array();
    }

    /**
     * Method to remember (save) form parameters to database.
     * Button "Remember" functionality.
     *
     * @param   array  $data  The form data.
     *
     * @return  boolean  True on success, false on failure.
     *
     * @since   1.0.0
     */
    public function remember($data)
    {
        try {
            $app = Factory::getApplication();
            
            // Validate form data
            $form = $this->getForm($data, false);
            if (!$form) {
                $app->enqueueMessage(Text::_('COM_KUNENATOPIC2ARTICLE_FORM_OBJECT_EMPTY'), 'error');
                return false;
            }

            $validData = $this->validate($form, $data);
            if ($validData === false) {
                $app->enqueueMessage(Text::_('COM_KUNENATOPIC2ARTICLE_FORM_VALIDATION_FAILED'), 'error');
                return false;
            }

            $db = $this->getDatabase();
            
            // Clear existing parameters
            $query = $db->getQuery(true);
            $query->delete('#__kunenatopic2article_params');
            $db->setQuery($query);
            $db->execute();

            // Insert new parameters
            $query = $db->getQuery(true);
            $query->insert('#__kunenatopic2article_params')
                  ->columns(array(
                      'topic_id', 'article_category', 'max_article_size', 'post_transfer_scheme',
                      'ignored_authors', 'reminder_lines', 'post_author', 'post_creation_date',
                      'post_creation_time', 'post_ids', 'post_title', 'kunena_post_link'
                  ))
                  ->values(
                      $db->quote($validData['topic_id']) . ',' .
                      $db->quote($validData['article_category']) . ',' .
                      $db->quote($validData['max_article_size']) . ',' .
                      $db->quote($validData['post_transfer_scheme']) . ',' .
                      $db->quote($validData['ignored_authors']) . ',' .
                      $db->quote($validData['reminder_lines']) . ',' .
                      $db->quote($validData['post_author']) . ',' .
                      $db->quote($validData['post_creation_date']) . ',' .
                      $db->quote($validData['post_creation_time']) . ',' .
                      $db->quote($validData['post_ids']) . ',' .
                      $db->quote($validData['post_title']) . ',' .
                      $db->quote($validData['kunena_post_link'])
                  );

            $db->setQuery($query);
            $result = $db->execute();

            if ($result) {
                $this->paramsRemembered = true;
                $app->enqueueMessage(Text::_('COM_KUNENATOPIC2ARTICLE_SUCCESS_PARAMETERS_SAVED'), 'message');
                return true;
            } else {
                $app->enqueueMessage(Text::_('COM_KUNENATOPIC2ARTICLE_SAVE_FAILED'), 'error');
                return false;
            }

        } catch (\Exception $e) {
            $app = Factory::getApplication();
            $app->enqueueMessage(Text::sprintf('COM_KUNENATOPIC2ARTICLE_SAVE_FAILED') . ': ' . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Method to reset parameters to default values.
     * Button "Reset" functionality.
     * @return  boolean  True on success, false on failure.
     * @since   1.0.0
     */
     public function reset()
    {
        $db = JFactory::getDbo();
    
    try {
        // Сброс к значениям по умолчанию
        $query = $db->getQuery(true)
            ->update($db->quoteName('#__kunenatopic2article_params'))
            ->set($db->quoteName('topic_selection') . ' = 0')
            ->set($db->quoteName('article_category') . ' = 0')
            ->set($db->quoteName('post_transfer_scheme') . ' = 1')
            ->set($db->quoteName('max_article_size') . ' = 40000')
            ->set($db->quoteName('post_author') . ' = 1')
            ->set($db->quoteName('post_creation_date') . ' = 0')
            ->set($db->quoteName('post_creation_time') . ' = 0')
            ->set($db->quoteName('post_ids') . ' = 0')
            ->set($db->quoteName('post_title') . ' = 0')
            ->set($db->quoteName('kunena_post_link') . ' = 0')
            ->set($db->quoteName('reminder_lines') . ' = 0')
            ->set($db->quoteName('ignored_authors') . ' = ' . $db->quote(''))
            ->where($db->quoteName('id') . ' = 1');
            
        $db->setQuery($query);
        $db->execute();
        
        // Устанавливаем флаг, чтобы заблокировать "Create Artikels"
        $this->setFlag('reset_performed', true);
        
        JFactory::getApplication()->enqueueMessage(
            'Параметры сброшены к значениям по умолчанию', 
            'message'
        );
        return true;
        
    } catch (Exception $e) {
        JFactory::getApplication()->enqueueMessage(
            'Ошибка при сбросе параметров: ' . $e->getMessage(), 
            'error'
        );
        return false;
    }
}
    /**
     * Method for "Create Articles" button functionality.
     * Currently only resets the paramsRemembered flag.
     * In the future will delegate control to ArticleController.php
     *
     * @return  boolean  True on success, false on failure.
     *
     * @since   1.0.0
     */
    public function createArticles()
    {
        try {
            // Reset the paramsRemembered flag
            $this->paramsRemembered = false;
            
            // TODO: In the future, delegate control to ArticleController.php
            // $articleController = new ArticleController();
            // return $articleController->createFromTopic($data);
            
            $app = Factory::getApplication();
            $app->enqueueMessage(Text::_('COM_KUNENATOPIC2ARTICLE_FEATURE_COMING_SOON'), 'message');
            
            return true;

        } catch (\Exception $e) {
            $app = Factory::getApplication();
            $app->enqueueMessage(Text::sprintf('COM_KUNENATOPIC2ARTICLE_ARTICLE_CREATION_ERROR') . ': ' . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Get the current state of paramsRemembered flag
     *
     * @return  boolean  True if parameters are remembered, false otherwise
     *
     * @since   1.0.0
     */
    public function getParamsRemembered()
    {
        return $this->paramsRemembered;
    }

    /**
     * Method to get a table object, load it if necessary.
     *
     * @param   string  $name     The table name. Optional.
     * @param   string  $prefix   The class prefix. Optional.
     * @param   array   $options  Configuration array for model. Optional.
     *
     * @return  Table  A Table object
     *
     * @since   1.0.0
     * @throws  \Exception
     */
    public function getTable($name = '', $prefix = '', $options = array())
    {
        // This model doesn't use a specific table for form data
        return null;
    }
}
