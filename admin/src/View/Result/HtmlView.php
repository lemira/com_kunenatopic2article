namespace Joomla\Component\KunenaTopic2Article\Administrator\View\Result;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

class HtmlView extends BaseHtmlView
{
$app = Factory::getApplication();
error_log('All flash messages: '.print_r($app->getMessageQueue(), true));

    protected $articles = [];
    protected $emailsSent = false;
    protected $emailsSentTo = [];

public function display($tpl = null): void
{
    $app = Factory::getApplication();
    
    // Шаг 1: Извлечение сырой JSON-строки из flash-сообщения
    $jsonString = $this->extractJsonFromMessages($app);
    
    // Шаг 2: Декодирование JSON в массив
    $decodedData = $this->decodeJsonData($jsonString);
    
    // Шаг 3: Валидация и разбор данных
    $this->validateAndAssignData($decodedData);
    
    parent::display($tpl);
}

/**
 * Шаг 1: Извлекаем сырую JSON-строку из сообщений
 */
private function extractJsonFromMessages($app): string
{
    foreach ($app->getMessageQueue() as $message) {
        if ($message['type'] === 'kunena-result-data') {
            error_log('Raw JSON extracted: ' . $message['message']);
            return $message['message'];
        }
    }
    throw new RuntimeException('No JSON data found in messages');
}

/**
 * Шаг 2: Декодируем JSON
 */
private function decodeJsonData(string $jsonString): array
{
    $data = json_decode($jsonString, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log('JSON decode failed: ' . json_last_error_msg());
        throw new RuntimeException('Invalid JSON data');
    }
    
    error_log('Decoded data: ' . print_r($data, true));
    return $data;
}

/**
 * Шаг 3: Проверяем и распределяем данные
 */
private function validateAndAssignData(array $data): void
{
    if (empty($data['articles'])) {
        throw new RuntimeException('No articles found in data');
    }
    
    $this->articles = $data['articles'];
    $this->emailsSent = $data['emails']['sent'] ?? false;
    $this->emailsSentTo = $data['emails']['recipients'] ?? [];
    
    error_log('Data assigned to view');
}

}
