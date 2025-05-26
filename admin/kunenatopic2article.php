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

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Language\Text;

// Access check.
if (!Factory::getUser()->authorise('core.manage', 'com_kunenatopic2article')) {
    throw new \Exception(Text::_('JERROR_ALERTNOAUTHOR'), 403);
}

$app = Factory::getApplication();
$input = $app->input;

// Get an instance of the controller prefixed by KunenaTopic2Article
$controller = BaseController::getInstance('KunenaTopic2Article');

// Perform the Request task
$task = $input->getCmd('task');

try {
    $controller->execute($task);
} catch (Exception $e) {
    $app->enqueueMessage($e->getMessage(), 'error');
}

// Redirect if set by the controller
$controller->redirect();
