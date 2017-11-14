<?php
namespace Craft;

/**
 * Auction Management for Craft CMS
 *
 * @author    Peter van den Broek <p.vdbroek@outlook.com>
 * @copyright Copyright (c) 2017, VSR Partners
 */
class AuctionManagementPlugin extends BasePlugin
{

    /**
     * @return string
     */
    function getName(): string
    {
        return Craft::t('Auction Management');
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        return '1.0.0';
    }

    public function getSchemaVersion()
    {
        return '1.0.0';
    }

    /**
     * @return string
     */
    function getDeveloper(): string
    {
        return 'WeAuction';
    }

    /**
     * @return string
     */
    function getDeveloperUrl(): string
    {
        return 'http://weauction.nl';
    }

    /**
     * @return string
     */
    public function getPluginUrl()
    {
        return 'https://github.com/petervdbroek/weauction-craft-auctionmanagement';
    }

    /**
     * @return string
     */
    public function getReleaseFeedUrl()
    {
        return 'https://raw.githubusercontent.com/petervdbroek/weauction-craft-auctionmanagement/master/releases.json';
    }

    /**
     * @return array
     */
    public function defineSettings(): array
    {
        return [
            'ws_url' => [AttributeType::String, 'default' => 'wss://streaming.vsrpartners.nl:8443'],
            'stream_name' => [AttributeType::String, 'default' => ''],
            'stream_started' => [AttributeType::String, 'default' => ''],
            'channel_name' => [AttributeType::String, 'default' => 'events'],
            'stream_type' => [AttributeType::String, 'default' => 'flashphoner'],
        ];
    }

    /**
     * @return string
     */
    public function getSettingsHtml(): string
    {
        $filesystemConfigPath = craft()->path->getConfigPath() . 'auctionmanagement.php';

        return craft()->templates->render('auctionmanagement/settings', [
            'settings' => $this->getSettings(),
            'filesystemConfigExists' => (bool)IOHelper::fileExists($filesystemConfigPath)
        ]);
    }

    /**
     * @return bool
     */
    public function hasCpSection(): bool
    {
        if (!craft()->isConsole()) {
            return (craft()->userSession->isAdmin() || craft()->userSession->checkPermission('accessPlugin-auctionManagement'));
        }
        return false;
    }

    /**
     * @return array
     */
    public function registerCpRoutes (): array
    {
        return [
            'auctionmanagement' => ['action' => 'auctionManagement/index'],
            'auctionmanagement/events' => ['action' => 'auctionManagement/eventsPage'],
            'auctionmanagement/objects' => ['action' => 'auctionManagement/objectsPage'],
            'auctionmanagement/auctioneer' => ['action' => 'auctionManagement/auctioneerPage'],
            'auctionmanagement/clerk' => ['action' => 'auctionManagement/clerkPage'],
            'auctionmanagement/streamer' => ['action' => 'auctionManagement/streamerPage'],
        ];
    }

    /**
     * @return array
     */
    public function registerUserPermissions(): array
    {
        return [
            'startStreamer' => ['label' => Craft::t('Start Streamer')],
            'manageEvents' => ['label' => Craft::t('Manage events')],
        ];
    }
}