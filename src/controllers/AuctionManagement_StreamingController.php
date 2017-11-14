<?php

namespace Craft;

/**
 * Class AuctionManagement_StreamingController
 *
 * @package Craft
 */
class AuctionManagement_StreamingController extends BaseController
{
    public function actionChangeStreamer()
    {
        $this->requirePostRequest();

        $auctionManagement = craft()->plugins->getPlugin('auctionmanagement');
        $newSettings['stream_started'] = time();
        $newSettings['stream_type'] = $_POST['stream_type'];
        craft()->userSession->setNotice('Streamer changed');

        if (isset($_POST['start'])) { // Start VSR Stream
            $newSettings['stream_name'] = 'room_'.time();
            craft()->userSession->setNotice('Streamer started');
        }
        if (isset($_POST['stop'])) { // Stop VSR Stream
            $newSettings['stream_name'] = '';
            craft()->userSession->setNotice('Streamer stopped');
        }

        craft()->plugins->savePluginSettings($auctionManagement, $newSettings);

        craft()->request->redirect('/admin/auctionmanagement/streamer');
    }

    /**
     * Start stream
     */
    public function actionCreateNewStream(): void
    {
        $this->requirePostRequest();

        /** @var AuctionManagementPlugin $auctionManagement */
        $auctionManagement = craft()->plugins->getPlugin('auctionmanagement');
        $settings = $auctionManagement->getSettings();
        $oldStreamName = $settings['stream_name'];
        $newStreamName = 'room_'.time();

        $newSettings['stream_name'] = $newStreamName;
        $newSettings['stream_started'] = time();

        craft()->plugins->savePluginSettings($auctionManagement, $newSettings);

        $this->returnJson(
            [
                'oldStreamName' => $oldStreamName,
                'streamName' => $newStreamName,
            ]
        );
    }

    /**
     * Stop stream
     */
    public function actionStopStream(): void
    {
        $this->requirePostRequest();

        /** @var AuctionManagementPlugin $auctionManagement */
        $auctionManagement = craft()->plugins->getPlugin('auctionmanagement');
        $settings = $auctionManagement->getSettings();
        $oldStreamName = $settings['stream_name'];
        $newSettings['stream_name'] = '';
        $newSettings['stream_started'] = time();
        craft()->plugins->savePluginSettings($auctionManagement, $newSettings);

        // Don't unpublish stream in flashphoner
        if ($settings['stream_type'] != 'flashphoner') {
            $oldStreamName = '';
        }

        $this->returnJson(
            [
                'streamName' => $oldStreamName,
            ]
        );
    }
}
