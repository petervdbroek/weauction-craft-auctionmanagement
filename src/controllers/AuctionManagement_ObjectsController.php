<?php

namespace Craft;

/**
 * Class AuctionManagement_EventsController
 * @package Craft
 */
class AuctionManagement_ObjectsController extends BaseController
{
    protected $allowAnonymous = ['actionCallback'];

    /**
     * Start object
     */
    public function actionStart(): void
    {
        $params = $this->getActionParams();
        if (isset($params['object_id']) && is_numeric($params['object_id']) && isset($params['event_id']) && is_numeric($params['event_id'])) {
            $object = craft()->auctionManagement_object->start($params['object_id'], $params['event_id']);
            craft()->request->redirect('/admin/auctionmanagement/clerk?event_id=' . $object->event_id . '&object_id=' . $object->object_id);
        }
    }

    public function actionConfirm(): void
    {
        $params = $this->getActionParams();
        if (isset($params['object_id']) && is_numeric($params['object_id']) && isset($params['event_id']) && is_numeric($params['event_id'])) {
            $object = craft()->auctionManagement_object->confirmObject($params['object_id'], $params['event_id']);
            craft()->request->redirect('/admin/auctionmanagement/objects?id='.$object->event_id);
        }
    }

    public function actionDelete(): void
    {
        $params = $this->getActionParams();
        if (isset($params['object_id']) && is_numeric($params['object_id']) && isset($params['event_id']) && is_numeric($params['event_id'])) {
            $object = craft()->auctionManagement_object->deleteObject($params['object_id'], $params['event_id']);
            craft()->request->redirect('/admin/auctionmanagement/clerk?event_id=' . $object->event_id . '&object_id=' . $object->object_id);
        }
    }

    /**
     * Callback from bidserver
     */
    public function actionCallback(): void
    {
        $params = $this->getActionParams();
        if (isset($params['id']) && is_numeric($params['id'])) {
            try {
                if (craft()->auctionManagement_object->handleCallback($params['id'])) {
                    http_response_code(200);
                } else {
                    http_response_code(500);
                }
            } catch (\TypeError $exception) {
                http_response_code(500);
            }
        } else {
            http_response_code(500);
        }
        die();
    }

    /**
     * Download bidlog
     */
    public function actionDownloadBidLog(): void
    {
        $params = $this->getActionParams();
        if (isset($params['object_id']) && is_numeric($params['object_id']) && isset($params['event_id']) && is_numeric($params['event_id'])) {
            $object = craft()->entries->getEntryById($params['object_id']);
            if (!$object) {
                return;
            }

            $objectRecord = AuctionManagement_ObjectRecord::model()->findById([
                'object_id' => $params['object_id'],
                'event_id' => $params['event_id']
            ]);
            if (!$objectRecord) {
                return;
            }

            header('Content-Disposition: attachment; filename="Bidlog ' . $object->title . '.csv";');
            header('Content-Type: application/csv');

            echo $objectRecord['log'];

            die();
        }
    }
}
