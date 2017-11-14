<?php
namespace Craft;

/**
 * Class AuctionManagementVariable
 * @package Craft
 */
class AuctionManagementVariable
{
    /**
     * @return BaseModel
     */
    public function settings(): BaseModel
    {
        return craft()->plugins->getPlugin('auctionmanagement')->getSettings();
    }

    /**
     * @return array
     */
    public function events(): array
    {
        $settings = $this->settings();
        $criteria = craft()->elements->getCriteria(ElementType::Entry);
        $criteria->section = $settings['channel_name'];
        return $criteria->find();
    }

    /**
     * @param int $event_id
     * @return array objects
     */
    public function objects(int $event_id): array
    {
        return craft()->auctionManagement_event->getObjects($event_id);
    }

    /**
     * @param int $object_id
     * @param int $event_id
     *
     * @return AuctionManagement_ObjectRecord|null objects|null
     */
    public function getObject(int $object_id, int $event_id): ?AuctionManagement_ObjectRecord
    {
        return AuctionManagement_ObjectRecord::model()->findById([
            'object_id' => $object_id,
            'event_id' => $event_id
        ]);
    }

    /**
     * @param AuctionManagement_ObjectRecord $object
     *
     * @return null|\StdClass
     */
    public function getUser(AuctionManagement_ObjectRecord $object): ?\StdClass
    {
        if (null !== $object['user_id']) {
            return craft()->myAuction_login->getUserById($object['user_id']);
        }
        return null;
    }

    /**
     * @return int
     */
    public function numEvents(): int
    {
        return sizeof($this->events());
    }

    /**
     * @return int
     */
    public function numObjects(): int
    {
        return sizeof($this->objects(0));
    }

    /**
     * @param int $event_id
     * @return AuctionManagement_ObjectRecord|null
     */
    public function activeObject(int $event_id): ?AuctionManagement_ObjectRecord
    {
        return craft()->auctionManagement_event->getActiveObject($event_id);
    }

    /**
     * @param int $event_id
     * @param int $object_id
     * @param int $limit
     * @return array|null
     */
    public function nextObjects(int $event_id, int $object_id, int $limit): ?array
    {
        return craft()->auctionManagement_event->getNextObjects($event_id, $object_id, $limit);
    }

    /**
     * @param int $event_id
     * @param int $object_id
     * @param int $limit
     * @return array|null
     */
    public function previousObjects(int $event_id, int $object_id, int $limit): ?array
    {
        return craft()->auctionManagement_event->getPreviousObjects($event_id, $object_id, $limit);
    }

    /**
     * @param int $event_id
     *
     * @return bool
     */
    public function liveAuctionButton(int $event_id = 0): bool
    {
        return craft()->auctionManagement_event->liveAuctionButton($event_id);
    }

    /**
     * @param int $event_id
     *
     * @return bool
     */
    public function sendToBidPage(int $event_id = 0): bool
    {
        return craft()->auctionManagement_event->sendToBidPage($event_id);
    }

    /**
     * @param AuctionManagement_ObjectRecord $object
     * @return bool
     */
    public function floorBid(AuctionManagement_ObjectRecord $object): bool
    {
        return ($object->user_id) ? false : true;
    }

    /**
     * @param int    $limit
     * @param string $order
     *
     * @return array|null
     */
    public function homepageObjects(int $limit, string $order = 'RAND()'): ?array
    {
        return craft()->auctionManagement_object->getHomepageObjects(craft()->getLanguage(), $limit, $order);
    }

    /**
     * @param int $object_id
     * @return bool
     */
    public function showResult(int $object_id): bool
    {
        $event_id = craft()->auctionManagement_object->getLastEventIdFromObject($object_id);
        if (null === $event_id) {

            return false;
        }

        return craft()->auctionManagement_event->showResult($event_id);
    }
}