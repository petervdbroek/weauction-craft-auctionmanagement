<?php

namespace Craft;

use Guzzle\Http\Exception\ClientErrorResponseException;
use Guzzle\Http\Message\Response;

/**
 * Class AuctionManagement_ObjectService
 *
 * @package Craft
 */
class AuctionManagement_ObjectService extends BaseApplicationComponent
{
    /**
     * @param int $object_id
     * @param int $event_id
     *
     * @return AuctionManagement_ObjectRecord|null
     */
    public function start(int $object_id, int $event_id): ?AuctionManagement_ObjectRecord
    {
        /** @var EntryModel $object */
        $object = craft()->entries->getEntryById($object_id);
        if (!$object) {
            craft()->userSession->setError('No object found');

            return null;
        }

        /** @var AuctionManagement_ObjectRecord $object */
        $objectRecord = AuctionManagement_ObjectRecord::model()->findById(
            [
                'object_id' => $object_id,
                'event_id' => $event_id,
            ]
        );

        if (!$objectRecord) {
            craft()->userSession->setError('No object found');

            return null;
        }

        // Upsert object
        try {
            $data = json_encode(
                [
                    "objectEid" => $event_id.'_'.$object_id,
                    "name" => $object->horseName,
                    "currency" => "EUR",
                    "openingBid" => $object->openingBid,
                    "initialBidStep" => $object->initialBidstep,
                    "endCallbackUrl" => craft()->config->get('environmentVariables')['baseUrl']
                        .'actions/auctionManagement/objects/callback?id='.$event_id.'_'.$object_id,
                ]
            );

            craft()->auctionManagement_api->doRequest('action/UpsertAscendingObject', 'post', $data);

            // Remove active object from current active object
            AuctionManagement_ObjectRecord::model()->updateAll(
                ['active_object' => false],
                'active_object = "1" AND event_id = :event_id',
                [
                    ':event_id' => $event_id,
                ]
            );

            $objectRecord->setAttribute('status', 'started');
            $objectRecord->setAttribute('active_object', true);
            $objectRecord->save();

            // Put JSON object ready for bidpage
            $this->saveJSONForBidPage($object, $event_id);

            // Save teaser from object in event messages
            $messages = ['en' => '', ''];
            foreach (craft()->i18n->getSiteLocales() AS $locale) {
                /** @var EntryModel $temp */
                $temp = craft()->entries->getEntryById($object_id, $locale);
                $teaser = $temp->getContent()->getAttribute('teaser');
                if (!$teaser) {
                    $teaser = '';
                }
                $messages[(string)$locale] = $teaser;
            }

            craft()->auctionManagement_event->addMessage($event_id, $messages);

            craft()->userSession->setNotice('Object succesfully started');

            return $objectRecord;
        } catch (\Exception $e) {
            craft()->userSession->setError($e->getMessage());

            return $objectRecord;
        }
    }

    /**
     * @param EntryModel $object
     * @param int        $event_id
     *
     * @internal param int $object_id
     */
    private function saveJSONForBidPage(EntryModel $object, int $event_id): void
    {
        $attributes = [
            'horseName',
            'category',
            'gender',
            'father',
            'mother',
            'nationality',
            'dateOfBirth',
            'breeder',
            'soldWitVat',
            'profitom',
            'owner',
            'seller',
            'teaser',
        ];

        $output = $object->getContent()->getAttributes($attributes);

        if ($object->details) {
            $output['details'] = $object->details->getParsedContent();
        }

        if ($object->pedigree[0]) {
            $output['pedigree'] = $object->pedigree[0]->url;
        }

        if ($object->photos[0]) {
            $output['photo'] = $object->photos[0]->url;
        }

        if ($object->videos[0]) {
            $output['youtube'] = $object->videos[0]->youtubeEmbedUrl;
            $output['mute_audio'] = ($object->videoWithAudio === '1') ? '0' : '1';
            if ($output['mute_audio'] == '1') {
                $explodedList = explode('/', $output['youtube']);
                if (is_array($explodedList)) {
                    $output['youtube_id'] = end($explodedList);
                }
            }
        }

        $output['event_id'] = $event_id;
        $output['id'] = $object->id;

        $upcompingObjects = craft()->auctionManagement_event->getNextObjects($event_id, $object->id, 4);

        if ($upcompingObjects) {
            foreach ($upcompingObjects AS $upcompingObject) {
                $upcompingObject = craft()->entries->getEntryById($upcompingObject->object_id);
                if ($upcompingObject) {
                    $upcoming = ['horseName' => $upcompingObject->horseName];
                    if ($upcompingObject->photos[0]) {
                        $upcoming['photo'] = $upcompingObject->photos[0]->url;
                    }
                    $output['upcoming'][] = $upcoming;
                }
            }
        }

        $filename = __DIR__.'/../../../storage/temp.json';
        file_put_contents($filename, json_encode($output));
        $s3Filename = 'activeObject_'.$event_id.'.json';

        // Add file 10 times for loadbalancing is S3
        for ($i = 0; $i < 10; $i++) {
            // Amazon JSON Asset ID (4 - 13)
            $folder = craft()->assets->findFolder(["sourceId" => ($i + 4)]);
            craft()->assets->insertFileByLocalPath(
                $filename, // Local path to file.
                $s3Filename, // Name file should be given when saved.
                $folder->id,
                AssetConflictResolution::Replace
            );
        }
    }

    /**
     * @param int $object_id
     * @param int $event_id
     *
     * @return AuctionManagement_ObjectRecord
     */
    public function deleteObject(int $object_id, int $event_id): AuctionManagement_ObjectRecord
    {
        $object = craft()->entries->getEntryById($object_id);
        if (!$object) {
            craft()->userSession->setError('No object found');

            return null;
        }

        /** @var AuctionManagement_ObjectRecord $object */
        $objectRecord = AuctionManagement_ObjectRecord::model()->findById(
            [
                'object_id' => $object_id,
                'event_id' => $event_id,
            ]
        );

        if (!$objectRecord) {
            craft()->userSession->setError('No object found');

            return null;
        }

        // Delete object
        try {
            $data = json_encode(
                [
                    "objectEid" => $event_id.'_'.$object_id,
                ]
            );

            craft()->auctionManagement_api->doRequest('action/DestroyAscendingObject', 'post', $data);

            $objectRecord->setAttribute('status', 'stopped');
            $objectRecord->setAttribute('confirmed', '0');
            $objectRecord->save();

            craft()->userSession->setNotice('Object succesfully deleted from bidserver');

            return $objectRecord;
        } catch (\Exception $e) {
            craft()->userSession->setError($e->getMessage());

            return $objectRecord;
        }
    }

    /**
     * @param string $objectEid
     *
     * @return bool
     */
    public function handleCallback(string $objectEid): bool
    {
        /** @var Response $response */
        $response = craft()->auctionManagement_api->doRequest('fetch/Object/'.$objectEid, 'get');
        $objectLog = json_decode($response->getBody());

        [$event_id, $object_id] = explode('_', $objectEid);

        /** @var AuctionManagement_ObjectRecord $object */
        $objectRecord = AuctionManagement_ObjectRecord::model()->findById(
            [
                'object_id' => $object_id,
                'event_id' => $event_id,
            ]
        );

        if (!$objectRecord) {
            return false;
        }

        $this->completeObject($objectEid, $objectLog, $objectRecord);

        return true;
    }

    /**
     * @param string                         $objectEid
     * @param \StdClass                      $objectLog
     * @param AuctionManagement_ObjectRecord $objectRecord
     */
    public function completeObject(
        string $objectEid,
        \StdClass $objectLog,
        AuctionManagement_ObjectRecord $objectRecord
    ): void {
        $object = $objectLog->object->{$objectEid};
        $users = $object->user;

        if (!isset($object->bid->{$object->lastBidId})) {
            $objectRecord->setAttribute('amount', 0);
        } else {
            $winningBid = $object->bid->{$object->lastBidId};
            $csv = "ID;User ID;Displayname;Datetime;Cancelled;Denied;Amount\n";
            // Save bidlog as CSV
            foreach ($object->bid AS $bid) {
                $datetime = date(
                        "d-m-Y H:i:s",
                        substr(
                            $bid->created,
                            0,
                            strlen($bid->created) - 3
                        )
                    ) . "." . substr(
                        $bid->created,
                        -3,
                        strlen($bid->created) - 1
                    );
                $username = (null === $bid->userId) ? 'in room' : $users->{$bid->userId}->name;
                $csv .= $bid->id . ";" .
                    $bid->userId . ";" .
                    "\"" . $username . "\";" .
                    $datetime . ";" .
                    var_export($bid->cancelled, true) . ";" .
                    var_export($bid->denied, true) .
                    ";" . $bid->amount . "\n";
            }

            $objectRecord->setAttribute('log', $csv);
            $objectRecord->setAttribute('user_id', $winningBid->userId);
            $objectRecord->setAttribute('amount', $winningBid->amount);
        }
        $objectRecord->setAttribute('status', 'completed');
        $objectRecord->save();
    }

    /**
     * @param int $object_id
     * @param int $event_id
     *
     * @return AuctionManagement_ObjectRecord
     */
    public function confirmObject(int $object_id, int $event_id): AuctionManagement_ObjectRecord
    {
        /** @var AuctionManagement_ObjectRecord $object */
        $objectRecord = AuctionManagement_ObjectRecord::model()->findById(
            [
                'object_id' => $object_id,
                'event_id' => $event_id,
            ]
        );

        if (!$objectRecord) {
            craft()->userSession->setError('No object found');

            return null;
        }

        if ($objectRecord->status != 'completed') {
            craft()->userSession->setError(Craft::t('Object wasn\'t completed'));

            return $objectRecord;
        }

        if ($objectRecord->user_id) { // If highest bidder = user
            $this->sendMailToHighestBidder($objectRecord->user_id, $objectRecord->title, $objectRecord->amount);
        }

        $objectRecord->setAttribute('confirmed', '1');
        $objectRecord->save();

        // Save result in CMS
        $this->syncObjectResult($object_id, $event_id);

        return $objectRecord;
    }

    /**
     * @param string $userId
     * @param string $objectName
     * @param float  $amount
     *
     * @return bool
     */
    private function sendMailToHighestBidder(string $userId, string $objectName, float $amount): bool
    {
        $user = craft()->myAuction_login->getUserById($userId);
        if ($user->email[0]->value) {
            $recipients = $user->email[0]->value;

            $replaceVars = [
                'object' => $objectName,
                'amount' => '&euro; '.number_format($amount, 0, ',', '.'),
                'name' => $user->profile->firstname,
            ];

            $locale = ($user->profile->language) ? $user->profile->language : 'en';
            /** @var EntryModel $mail */
            $mail = craft()->myAuction_craft->getMail($locale, 'highestBid');

            $body = $mail->getContent()->getAttribute('mailBody');
            $subject = nl2br($mail->getContent()->getAttribute('subject'));

            foreach ($replaceVars AS $replaceVar => $value) {
                $body = str_replace('%'.$replaceVar.'%', $value, $body);
                $subject = str_replace('%'.$replaceVar.'%', $value, $subject);
            }

            $email = new EmailModel();

            $bcc = [
                [
                    'email' => 'info@emta-auctions.com',
                    'name' => 'EMTA Auctions',
                ],
                [
                    'email' => 'info@vsrpartners.nl',
                    'name' => 'VSR Partners',
                ],
            ];

            $email->toEmail = $recipients;
            $email->bcc = $bcc;
            $email->subject = $subject;
            $email->body = $body;

            if (craft()->email->sendEmail($email)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $userId
     *
     * @return bool
     */
    public function blockBidder(string $userId): bool
    {
        $objects = AuctionManagement_ObjectRecord::model()->findAllByAttributes(
            [
                'status' => 'started',
            ]
        );

        /** @var AuctionManagement_ObjectRecord $object */
        foreach ($objects AS $object) {
            $data = json_encode(
                [
                    "objectEid" => $object->event_id.'_'.$object->object_id,
                    "userEid" => $userId,
                ]
            );

            try {
                craft()->auctionManagement_api->doRequest('action/BlockUser', 'post', $data);

                return true;
            } catch (\Exception $e) {
                if ($e instanceof ClientErrorResponseException) {
                    switch ($e->getResponse()->getStatusCode()) {
                        case '404': // Conflict
                            return true;
                        break;
                    }
                }

                return false;
            }
        }
    }

    /**
     * @param string $userId
     *
     * @return bool
     */
    public function unBlockBidder(string $userId): bool
    {
        $objects = AuctionManagement_ObjectRecord::model()->findAllByAttributes(
            [
                'status' => 'started',
            ]
        );
        /** @var AuctionManagement_ObjectRecord $object */
        foreach ($objects AS $object) {
            $data = json_encode(
                [
                    "objectEid" => $object->event_id.'_'.$object->object_id,
                    "userEid" => $userId,
                ]
            );
            try {
                craft()->auctionManagement_api->doRequest('action/UnblockUser', 'post', $data);

                return true;
            } catch (\Exception $e) {
                if ($e instanceof ClientErrorResponseException) {
                    switch ($e->getResponse()->getStatusCode()) {
                        case '404': // Conflict
                            return true;
                        break;
                    }
                }

                return false;
            }
        }
    }

    /**
     * Sync a single objects result based on object id
     *
     * @param int $object_id
     * @param int $event_id
     *
     * @return bool
     */
    public function syncObjectResult(int $object_id, int $event_id): bool
    {

        $objectRecord = AuctionManagement_ObjectRecord::model()->findById(
            [
                'object_id' => $object_id,
                'event_id' => $event_id,
            ]
        );
        if (!$objectRecord) {
            return false;
        }

        $object = craft()->entries->getEntryById($object_id);

        if ($object && $objectRecord['status'] == 'completed') {
            $bidder = 'floor';
            if ($objectRecord['user_id']) {
                $bidder = 'internet';
            }
            $object->getContent()->setAttribute('sold', true);
            $object->getContent()->setAttribute('amount', $objectRecord['amount']);
            $object->getContent()->setAttribute('bidder', $bidder);

            return craft()->entries->saveEntry($object);
        }

        return false;
    }

    /**
     * @param string $locale
     * @param int    $limit
     * @param string $order
     *
     * @return array
     */
    public function getHomepageObjects(string $locale, int $limit, string $order): array
    {
        $criteria = craft()->elements->getCriteria(ElementType::Entry);
        $criteria->section = 'horses';
        $criteria->locale = $locale;
        $criteria->showOnHomepage = true;
        $criteria->limit = $limit;
        $criteria->order = $order;

        return $criteria->find();
    }

    /**
     * @param int $object_id
     * @return int|null
     */
    public function getLastEventIdFromObject(int $object_id): ?int
    {
        /** @var AuctionManagement_ObjectRecord $ojbect */
        $object = AuctionManagement_ObjectRecord::model()->findAll(
            [
                'condition' => 'object_id = :object_id',
                'params' => [
                    ':object_id' => $object_id,
                ],
                'order' => 'dateCreated DESC',
                'limit' => 1
            ]
        );
        if ($object && $object[0]) {
            return $object[0]['event_id'];
        }
        return null;
    }
}
