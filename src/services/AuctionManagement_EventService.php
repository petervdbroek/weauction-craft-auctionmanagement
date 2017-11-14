<?php

namespace Craft;

use Guzzle\Http\Message\Response;

/**
 * Class AuctionManagement_EventService
 * @package Craft
 */
class AuctionManagement_EventService extends BaseApplicationComponent
{
    /**
     * @param int $event_id
     * @return AuctionManagement_ObjectRecord|null
     */
    public function getActiveObject(int $event_id): ?AuctionManagement_ObjectRecord
    {
        /** @var AuctionManagement_ObjectRecord $activeObject */
        $activeObject = AuctionManagement_ObjectRecord::model()->findByAttributes(
            [
                'event_id' => $event_id,
                'active_object' => true
            ]
        );

        if (!$activeObject) {
            return null;
        }

        return $activeObject;
    }

    /**
     * @param int $event_id
     * @param int $object_id
     * @param int $limit
     * @return array|null
     */
    public function getNextObjects(int $event_id, int $object_id, int $limit): ?array
    {
        /** @var AuctionManagement_ObjectRecord $objectRecord */
        $objectRecord = AuctionManagement_ObjectRecord::model()->findById([
            'object_id' => $object_id,
            'event_id' => $event_id
        ]);

        $nextObjects = AuctionManagement_ObjectRecord::model()->findAll(
            [
                'condition' => 'event_id = :event_id AND auction_order > :auction_order',
                'params' => [
                    ':event_id' => $event_id,
                    ':auction_order' => $objectRecord->auction_order
                ],
                'order' => 'auction_order ASC',
                'limit' => $limit
            ]
        );

        if (!$nextObjects) { // Get latest completed object
            return null;
        }

        return $nextObjects;
    }

    /**
     * @param int $event_id
     * @param int $object_id
     * @param int $limit
     * @return array|null
     */
    public function getPreviousObjects(int $event_id, int $object_id, int $limit): ?array
    {
        /** @var AuctionManagement_ObjectRecord $objectRecord */
        $objectRecord = AuctionManagement_ObjectRecord::model()->findById([
            'object_id' => $object_id,
            'event_id' => $event_id
        ]);

        $previousObjects = AuctionManagement_ObjectRecord::model()->findAll(
            [
                'condition' => 'event_id = :event_id AND auction_order < :auction_order',
                'params' => [
                    ':event_id' => $event_id,
                    ':auction_order' => $objectRecord->auction_order
                ],
                'order' => 'auction_order DESC',
                'limit' => $limit
            ]
        );

        if (!$previousObjects) { // Get latest completed object
            return null;
        }

        return $previousObjects;
    }

    /**
     * @param int $event_id
     *
     * @return bool
     */
    public function liveAuctionButton(int $event_id): bool
    {
        $criteria = craft()->elements->getCriteria(ElementType::Entry);
        $criteria->section = 'auctions';
        if ($event_id !== 0) {
            $criteria->id = $event_id;
        }
        $criteria->limit = null;

        $liveAuctionButton = false;

        foreach ($criteria->find() AS $auction) {
            if ($auction->liveAuctionButton) {
                $liveAuctionButton = true;
            }
        }

        return $liveAuctionButton;
    }

    /**
     * @return bool
     */
    public function sendToBidPage(int $event_id): bool
    {
        $criteria = craft()->elements->getCriteria(ElementType::Entry);
        $criteria->section = 'auctions';
        if ($event_id !== 0) {
            $criteria->id = $event_id;
        }
        $criteria->limit = null;

        $sendToBidPage = false;

        foreach ($criteria->find() AS $auction) {
            if ($auction->sendToBidpage) {
                $sendToBidPage = true;
            }
        }

        return $sendToBidPage;
    }

    /**
     * @param int $event_id
     * @param array $messages
     * @return bool
     * @internal param string $message
     */
    public function addMessage(int $event_id, array $messages): bool
    {
        $event = craft()->entries->getEntryById($event_id);

        if ($event) {
            $matrix = craft()->fields->getFieldByHandle("messages");
            $matrixBlocks = craft()->matrix->getBlockTypesByFieldId($matrix->id);
            $block = new MatrixBlockModel();
            $matrixBlockId = $matrixBlocks[0]['id'];
            $block = $this->saveMessageBlock($event_id, $messages[craft()->getLanguage()], $matrix->id, $block, $matrixBlockId);

            foreach ($block->getLocales() AS $locale) {
                if (isset($messages[$locale]) && $locale != craft()->getLanguage()) {
                    $block = craft()->matrix->getBlockById($block->id, $locale);
                    $block = $this->saveMessageBlock($event_id, $messages[$locale], $matrix->id, $block, $matrixBlockId); // Matrix field's ID
                }
            }

            return true;
        }

        return false;
    }

    /**
     * @param int $event_id
     * @param string $message
     * @param int $matrixId
     * @param MatrixBlockModel $block
     * @param int $matrixBlockId
     * @return MatrixBlockModel
     */
    private function saveMessageBlock(int $event_id, string $message, int $matrixId, MatrixBlockModel $block, int $matrixBlockId): MatrixBlockModel
    {
        $block->fieldId = $matrixId; // Matrix field's ID
        $block->ownerId = $event_id; // ID of entry the block should be added to
        $block->typeId = $matrixBlockId;
        $block->getContent()->setAttributes([
            'message' => $message,
        ]);
        craft()->matrix->saveBlock($block, false);

        return $block;
    }

    /**
     * @param int $event_id
     * @return AuctionManagement_ObjectRecord|null
     */
    public function startFirstObject(int $event_id): ?AuctionManagement_ObjectRecord
    {
        /** @var AuctionManagement_ObjectRecord $firstObject */
        $firstObject = AuctionManagement_ObjectRecord::model()->findByAttributes(
            [
                'event_id' => $event_id,
            ],
            ['order' => 'auction_order ASC']
        );

        return craft()->auctionManagement_object->start($firstObject->object_id, $event_id);
    }

    /**
     * @param int $event_id
     */
    public function syncObjects(int $event_id): void
    {
        /** @var array $objects */
        $objects = craft()->entries->getEntryById($event_id)->objects;
        $first = true;
        $dateTime = new DateTime();
        $i = 0;
        foreach ($objects AS $o) {
            $i++;
            $object = AuctionManagement_ObjectRecord::model()->findByAttributes(
                [
                    'object_id' => $o->id,
                    'event_id' => $event_id
                ]
            );
            if (!$object) {
                $objectLog = [];
                try {
                    /** @var Response $response */
                    $response = craft()->auctionManagement_api->doRequest('fetch/Object/'.$event_id.'_'.$o->id, 'get');
                    $objectLog = json_decode($response->getBody());
                    $obj = $objectLog->object->{$event_id.'_'.$o->id};

                    $status = 'started';

                    if ($obj->phase == 'post') {
                        $status = 'completed';
                    }
                } catch (\Exception $e) {
                    $status = 'stopped';
                }

                $object = new AuctionManagement_ObjectRecord();
                $object->setAttribute('object_id', $o->id);
                $object->setAttribute('status', $status);
                $object->setAttribute('event_id', $event_id);

                if ($status == 'completed') {
                    craft()->auctionManagement_object->completeObject($event_id.'_'.$o->id, $objectLog, $object); // Get amount / user
                    $object->setAttribute('confirmed', '1');
                }
            }
            $object->setAttribute('title', $o->title);
            $object->setAttribute('auction_order', $i);
            $object->save();

            if ($first) {
                $dateTime = $object->getAttribute('dateUpdated');
                $first = false;
            }
        }

        if ($dateTime) {
            // Delete all objects not in event anymore
            AuctionManagement_ObjectRecord::model()->deleteAll(
                [
                    'condition' => 'event_id = :event_id AND dateUpdated < :date',
                    'params' => [
                        ':event_id' => $event_id,
                        ':date' => $dateTime->format('Y-m-d H:i:s', 'UTC')
                    ],
                ]
            );
        }

        craft()->userSession->setNotice('Objects successfully synced');
    }

    /**
     * Sync all object results
     *
     * @param int $event_id
     */
    public function syncObjectResults(int $event_id): void
    {
        /** @var array $objects */
        $objects = craft()->entries->getEntryById($event_id)->objects;

        /** @var EntryModel $entry */
        foreach ($objects AS $object) {
            craft()->auctionManagement_object->syncObjectResult($object->id, $event_id);
        }

        craft()->userSession->setNotice('Objects results successfully synced');
    }

    /**
     * @param int $event_id
     *
     * @return array objects
     */
    public function getObjects(int $event_id): array
    {
        $condition = [];
        if ($event_id > 0) {
            $condition = ['event_id' => $event_id];
        }

        return AuctionManagement_ObjectRecord::model()->findAllByAttributes(
            $condition,
            ['order' => 'auction_order']
        );
    }

    /**
     * @param int $event_id
     * @return bool
     */
    public function showResult(int $event_id): bool
    {
        /** @var EntryModel $event */
        $event = craft()->entries->getEntryById($event_id);
        if (null !== $event && $event->getContent()->getAttribute('showResultsColumn')) {

            return true;
        }

        return false;
    }

}
