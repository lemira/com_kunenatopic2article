<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_kunenatopic2article
 */

namespace Joomla\Component\KunenaTopic2Article\Administrator\Table;

\defined('_JEXEC') or die;

use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;

/**
 * Params Table class.
 */
class ParamsTable extends Table
{
    /**
     * Constructor.
     *
     * @param   DatabaseDriver  $db  A database connector object
     */
    public function __construct(DatabaseDriver $db)
    {
        parent::__construct('#__kunenatopic2article_params', 'id', $db);
    }
}
