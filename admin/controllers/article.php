<?php
defined('_JEXEC') or die;

class KunenaTopic2ArticleControllerArticle extends JControllerLegacy
{
    /**
     * Constructor.
     *
     * @param   array  $config  An optional associative array of configuration settings.
     */
    public function __construct($config = array())
    {
        parent::__construct($config);
    }

    /**
     * Create an article from selected topic
     *
     * @return void
     */
    public function create()
    {
        // Проверка токена безопасности
        JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));
        
        // Получение выбранных ID тем
        $cid = $this->input->get('cid', array(), 'array');
        JArrayHelper::toInteger($cid);
        
        if (empty($cid)) {
            $this->setMessage(JText::_('COM_KUNENATOPIC2ARTICLE_NO_TOPIC_SELECTED'), 'warning');
            $this->setRedirect(JRoute::_('index.php?option=com_kunenatopic2article&view=topics', false));
            return;
        }
        
        // Получение модели для создания статей
        $model = $this->getModel('Article');
        
        // Получение модели с параметрами (только для чтения)
        $paramsModel = $this->getModel('Topics');
        $params = $paramsModel->getTopicParams($cid[0]); // Предполагается, что такой метод существует
        
        // Создание статьи с использованием параметров
        $result = $model->createArticleFromTopic($cid[0], $params);
        
        if ($result) {
            $this->setMessage(JText::_('COM_KUNENATOPIC2ARTICLE_ARTICLE_CREATED_SUCCESSFULLY'));
        } else {
            $this->setMessage(JText::_('COM_KUNENATOPIC2ARTICLE_ARTICLE_CREATION_ERROR'), 'error');
        }
        
        $this->setRedirect(JRoute::_('index.php?option=com_kunenatopic2article&view=topics', false));
    }
}
