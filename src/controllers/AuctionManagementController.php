<?php

namespace Craft;

/**
 * Class AuctionManagementController
 * @package Craft
 */
class AuctionManagementController extends BaseController
{

    /**
     * @var array
     */
    public $subNav = [];

    /**
     * Initialize plugin
     */
    public function init(): void
    {
        $this->subNav = [
            'index' => ['label' => 'Dashboard', 'url'=>'auctionmanagement'],
        ];

        if (craft()->userSession->isAdmin() || craft()->userSession->checkPermission('manageEvents')) {
            $this->subNav['events'] = ['label' => 'Events', 'url' => 'auctionmanagement/events'];
        }

        if (craft()->userSession->isAdmin() || craft()->userSession->checkPermission('startStreamer')) {
            $this->subNav['streamer'] = ['label' => 'Streamer', 'url' => 'auctionmanagement/streamer'];
        }

        parent::init();
    }

    /**
     * Index action
     */
    public function actionIndex(): void
    {
        $this->renderTemplate('auctionmanagement/index', [
            'subnav' => $this->subNav,
            'selectedSubnavItem' => 'index',
        ]);
    }

    /**
     * Events page
     */
    public function actionEventsPage(): void
    {
        craft()->userSession->requirePermission('manageEvents');

        $this->renderTemplate('auctionmanagement/events', [
            'subnav' => $this->subNav,
            'selectedSubnavItem' => 'sitemap',
            'crumbs' => [
                ['label' => 'Auction Management', 'url' => 'index'],
                ['label' => 'Events', 'url' => 'events'],
            ],
        ]);
    }

    /**
     * Objects page
     */
    public function actionObjectsPage(): void
    {
        craft()->userSession->requirePermission('manageEvents');

        $this->renderTemplate('auctionmanagement/objects', [
            'subnav' => $this->subNav,
            'selectedSubnavItem' => 'sitemap',
            'crumbs' => [
                ['label' => 'Auction Management', 'url' => 'index'],
                ['label' => 'Events', 'url' => 'events'],
            ],
        ]);
    }

    /**
     * Clerk page
     */

    public function actionClerkPage(): void
    {
        craft()->userSession->requirePermission('clerk');

        craft()->templates->includeCssFile(craft()->config->get('bs_widget_base_url', 'auctionmanagement') . '/client.css');
        craft()->templates->includeJsResource('auctionmanagement/js/jquery.json.js');
        craft()->templates->includeJsResource('auctionmanagement/js/clerk.js');


        $this->renderTemplate('auctionmanagement/clerk', [
            'subnav' => $this->subNav,
            'selectedSubnavItem' => 'sitemap',
            'crumbs' => [
                ['label' => 'Auction Management', 'url' => 'index'],
                ['label' => 'Events', 'url' => 'events'],
            ],
        ]);
    }

    /**
     * Auctioneer page
     */
    public function actionAuctioneerPage(): void
    {
        craft()->userSession->requirePermission('manageEvents');

        craft()->templates->includeCssFile(craft()->config->get('bs_widget_base_url', 'auctionmanagement') . '/client.css');
        craft()->templates->includeJsResource('auctionmanagement/js/interactiveReload.js');

        $this->renderTemplate('auctionmanagement/auctioneer', [
            'subnav' => $this->subNav,
            'selectedSubnavItem' => 'sitemap',
            'crumbs' => [
                ['label' => 'Auction Management', 'url' => 'index'],
                ['label' => 'Events', 'url' => 'events'],
            ],
        ]);
    }



    /**
     * Streamer page
     */
    public function actionStreamerPage(): void
    {
        craft()->userSession->requirePermission('startStreamer');

        craft()->templates->includeJsResource('auctionmanagement/js/flashphoner.min.js');
        craft()->templates->includeJsResource('auctionmanagement/js/utils.js');
        craft()->templates->includeJsResource('auctionmanagement/js/streaming.js');

        $this->renderTemplate('auctionmanagement/streamer', [
            'subnav' => $this->subNav,
            'selectedSubnavItem' => 'redirects',
            'crumbs' => [
                ['label' => 'Auction Management', 'url' => 'index'],
                ['label' => 'Streamer', 'url' => 'streamer'],
            ],
        ]);
    }
}
