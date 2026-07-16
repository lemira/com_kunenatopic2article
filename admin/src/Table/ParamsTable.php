<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_kunenatopic2article
 *
 * @copyright   (C) 2025 Leonid Ratner. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\KunenaTopic2Article\Administrator\Table;

defined('_JEXEC') or die;

use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;
use Joomla\Component\KunenaTopic2Article\Administrator\Repository\ParamsRepository;

class ParamsTable extends Table
{
    public function __construct(DatabaseDriver $db)
    {
        (new ParamsRepository($db))->ensureTable();
        
        parent::__construct('#__kunenatopic2article_params', 'id', $db);
    }
}
