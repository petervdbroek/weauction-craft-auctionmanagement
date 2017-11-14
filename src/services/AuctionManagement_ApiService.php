<?php

namespace Craft;

use Guzzle\Http\Client;
use Guzzle\Http\Message\Response;

/**
 * Class AuctionManagement_ApiService
 *
 * @package Craft
 */
class AuctionManagement_ApiService extends BaseApplicationComponent
{
    /**
     * @param string $uri
     * @param string $type
     * @param string $data
     * @param array  $headers
     *
     * @return Response
     */
    public function doRequest(string $uri, string $type, string $data = '', array $headers = []): Response
    {
        $client = new Client(craft()->config->get('bs_asc_hy_api_base_url', 'auctionmanagement').'/');

        $options = [
            'timeout' => 20,
            'connect_timeout' => 100,
            'allow_redirects' => true,
        ];

        switch ($type) {
            case 'post':
                $request = $client->post($uri, $headers, null, $options);
                $request->setBody($data, 'application/json');
                break;
            default:
                $request = $client->get($uri, $headers, $options);
                break;
        }

        return $request->send();
    }
}