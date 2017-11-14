<?php

namespace Craft;

/**
 * Class AuctionManagement_EventsController
 * @package Craft
 */
class AuctionManagement_EventsController extends BaseController
{
    protected $allowAnonymous = ['actionActiveObject', 'actionStreamStarted'];

    public function actionStreamStarted()
    {
        $settings = craft()->plugins->getPlugin('auctionmanagement')->getSettings();
        $data['streamStarted'] = $settings['stream_started'];
        $this->returnJson($data);
    }

    public function actionActiveObject()
    {
        $params = $this->getActionParams();

        $activeObject = craft()->auctionManagement_event->getActiveObject($params['event_id']);

        if (!$activeObject) {
            echo json_encode([
                'id' => null
            ]);
            die();
        }

        $object = craft()->entries->getEntryById($activeObject->object_id);

        echo json_encode([
            'id' => $activeObject->object_id,
            'horseName' => $object->title
        ]);
        die();
    }

    public function actionAddMessage()
    {
        $this->requirePostRequest();

        if (isset($_POST['event_id']) && is_numeric($_POST['event_id']) && isset($_POST['messages']))
        {
            craft()->auctionManagement_event->addMessage($_POST['event_id'], $_POST['messages']);
            craft()->userSession->setNotice('Message successfully added');
        }
    }

    public function actionStartFirstObject(): void
    {
        $params = $this->getActionParams();
        if (isset($params['event_id']) && is_numeric($params['event_id'])) {
            $object = craft()->auctionManagement_event->startFirstObject($params['event_id']);
            craft()->request->redirect('/admin/auctionmanagement/clerk?event_id=' . $params['event_id'] . '&object_id=' . $object['object_id']);
        }
    }

    public function actionSyncObjects(): void
    {
        $params = $this->getActionParams();
        if (isset($params['event_id']) && is_numeric($params['event_id'])) {
            craft()->auctionManagement_event->syncObjects($params['event_id']);
            craft()->request->redirect('/admin/auctionmanagement/objects?id='.$params['event_id']);
        }
    }

    /**
     * Sync auction results into craft entries
     */
    public function actionSyncResults(): void
    {
        $params = $this->getActionParams();
        if (isset($params['event_id']) && is_numeric($params['event_id'])) {
            craft()->auctionManagement_event->syncObjectResults($params['event_id']);
            craft()->request->redirect('/admin/auctionmanagement');
        }
    }

    public function actionGetDefaultMessage()
    {
        $params = $this->getActionParams();
        $messages = array();
        foreach (craft()->i18n->getSiteLocales() AS $locale) {
            $criteria = craft()->elements->getCriteria(ElementType::Entry);
            $criteria->id = $params['id'];
            $criteria->locale = $locale;
            $entry = $criteria->first();
            if ($entry) {
                $messages[(string)$locale] = $entry->getContent()->getAttribute('message');
            }
        }

        $this->returnJson($messages);
    }
}
