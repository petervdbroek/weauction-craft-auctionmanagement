<?php
namespace Craft;

/**
 * Class AuctionManagement_ObjectRecord
 * @package Craft
 *
 * @property string $object_id
 * @property string $event_id
 * @property string $status
 * @property string $amount
 * @property string $user_id
 * @property string $log
 * @property string $auction_order
 * @property string $confirmed
 */
class AuctionManagement_ObjectRecord extends BaseRecord
{
    /**
     * Define table name
     *
     * @return string
     */
    public function getTableName(): string
    {
        return 'auctionmanagement_objects';
    }

    /**
     * Define table attributes
     *
     * @return array
     */
    protected function defineAttributes(): array
    {
        return [
            'object_id' => [
                AttributeType::Number,
                'required'      => true
            ],
            'event_id' => [
                AttributeType::Number,
                'required'      => true
            ],
            'title' => [
                AttributeType::String,
                'required'      => true
            ],
            'status' => [
                AttributeType::Enum,
                'required'      => true,
                'values'        => 'stopped,started,completed',
                'default'       => 'stopped'
            ],
            'amount' => [
                AttributeType::Number,
                'required'      => false,
            ],
            'user_id' => [
                AttributeType::String,
                'required'      => false,
            ],
            'log' => [
                AttributeType::String,
                'column' => ColumnType::Binary,
                'required'      => false,
            ],
            'auction_order' => [
                AttributeType::Number,
                'required'      => true,
                'default'       => 0
            ],
            'confirmed' => [
                AttributeType::Bool,
                'required'      => false,
                'default'       => 0
            ],
            'active_object' => [
                AttributeType::Bool,
                'required'      => false,
                'default'       => 0
            ]
        ];
    }

    /**
     * Define indexes
     *
     * @return array
     */
    public function defineIndexes(): array
    {
        return [
            [
                'columns' => ['object_id', 'event_id'],
                'unique' => true
            ],
        ];
    }

    /**
     * @return array
     */
    public function primaryKey(): array
    {
        return ['object_id', 'event_id'];
    }
}