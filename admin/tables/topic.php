defined('_JEXEC') or die;

class YourComponentTableTopic extends JTable
{
    public function __construct(&$db)
    {
        parent::__construct('#__kunenatopic2article_params', 'id', $db);
    }
}
