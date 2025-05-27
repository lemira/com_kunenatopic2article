<?php
\defined('_JEXEC') or die;

namespace Joomla\Component\KunenaTopic2Article\Administrator\Table;

use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;

class ParamsTable extends Table
{
    public function __construct(DatabaseDriver $db)
    {
        parent::__construct('#__kunenatopic2article_params', 'id', $db);
    }
}
