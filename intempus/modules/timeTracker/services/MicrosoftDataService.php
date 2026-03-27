<?php

namespace app\modules\timeTracker\services;

use app\modules\timeTracker\models\MicrosoftGroup;
use app\modules\timeTracker\models\MicrosoftLocation;
use app\modules\timeTracker\services\interfaces\ApiInterface;
use app\modules\timeTracker\traits\CoordinateTrait;
use yii\db\ActiveRecord;
use yii\db\Exception;

class MicrosoftDataService
{
    use CoordinateTrait;

    private ApiInterface $apiService;

    public function __construct()
    {
        $this->apiService = new MicrosoftService();
    }

    public function getGroups(): ?array
    {
        $queryParams = [
            '$select' => 'id,displayName,mail',
            '$top' => '500',
        ];
        $response = $this->apiService->requestGet('groups', $queryParams);
        $result = $response['value'] ?? null;
        return $result;
    }

    public function saveNewGroups(array $groups): int
    {
        $count = 0;
        foreach ($groups as $group) {
            $exists = MicrosoftGroup::find()->where(['microsoft_id' => $group['id']])->one();
            if (!$exists) {
                $microsoftGroup = new MicrosoftGroup();
                $microsoftGroup->name = $group['displayName'];
                $microsoftGroup->email = $group['mail'] ?? '';
                $microsoftGroup->microsoft_id = $group['id'];
                $microsoftGroup->save();
                $count++;
            } else {
                $exists->name = $group['displayName'];
                $exists->email = $group['mail'] ?? '';
                $exists->microsoft_id = $group['id'];
                $exists->save();
            }
        }
        return $count;
    }

    public function getLocations(): ?array
    {
        $date = new \DateTime();
        $date->modify('first day of this month');
        $startDate = $date->format('Y-m-d') . ' 00:00:00';

        $result = [];
        $groups = MicrosoftGroup::find()->all();
        foreach ($groups as $group) {
            $tmpLocations = [];
            $queryParams = [
                '$select' => 'subject,location',
                '$top' => '200',
                '$filter' => "start/dateTime gt '$startDate'",
            ];
            try {
                $response = $this->apiService->requestGet('groups/' . $group->microsoft_id . '/events', $queryParams);
                if (isset($response['value']) && $response['value']) {
                    foreach ($response['value'] as $event) {
                        if (isset($event['location']['displayName']) && $event['location']['displayName']) {
                            $tmpLocations[] = [
                                'displayName' => $event['location']['displayName']
                            ];
                        }
                    }
                }
                $result = array_merge($result, $tmpLocations);
            } catch (\GuzzleHttp\Exception\ClientException $e) {
            }
        }
        return $result;
    }

    public function saveNewLocations(array $locations): int
    {
        $count = 0;
        foreach ($locations as $location) {
            $exists = MicrosoftLocation::find()->where(['displayName' => $location['displayName']])->exists();
            if (!$exists) {
                $microsoftLocation = new MicrosoftLocation();
                $microsoftLocation->displayName = $location['displayName'];
                $microsoftLocation->microsoft_id = [];
                $microsoftLocation->save();
                $count++;
            }
        }
        return $count;
    }

    public function geocode(array $locations): int
    {
        $count = 0;
        foreach ($locations as $location) {
            sleep(2);
            try {
                $response = $this->getCodeByLocationv2($location['displayName']);

                $lat = $response['lat'] ?? null;
                $lon = $response['lng'] ?? null;

                if ($lat && $lon) {
                    $location->lat = $lat;
                    $location->lon = $lon;
                    $location->save();
                    $count++;
                }
            } catch (\Exception $e) {
            }
        }
        return $count;
    }

    /**
     * @throws Exception
     */
    public function groupsByNameAndDate($dateTimeStart): ?array
    {
        $utcTimeZone = new \DateTimeZone('UTC');
        $pacificTimeZone = new \DateTimeZone('America/Los_Angeles');

        $newLocations = [];
        $groups = MicrosoftGroup::getAvailable();

        $extendedStart = (new \DateTime($dateTimeStart))->modify('-1 day')->format('Y-m-d');
        $extendedEnd = (new \DateTime($dateTimeStart))->modify('+2 days')->format('Y-m-d');

        $targetDate = date('Y-m-d', strtotime($dateTimeStart));
        MicrosoftLocation::updateAll(
            ['microsoft_id' => []],
        );

        foreach ($groups as $group) {
            $queryParams = [
                '$select' => 'id,subject,bodyPreview,location, start, end, createdDateTime',
                '$top' => '200',
                '$filter' => "start/dateTime ge '" . $extendedStart . "' and start/dateTime lt '" . $extendedEnd . "'",
            ];

            $response = $this->apiService->requestGet(
                'groups/' . $group['microsoft_id'] . '/events',
                $queryParams
            );

            $locations = $response['value'] ?? null;

            foreach ($locations as $location) {
                $fullTime = $location['start']['dateTime'] ?? null;
                if (!$fullTime) {
                    continue;
                }

                $eventDate = new \DateTime($fullTime, $utcTimeZone);
                $eventDate->setTimezone($pacificTimeZone);
                $eventPacificDate = $eventDate->format('Y-m-d');
                $eventPacificTime = $eventDate->format('Y-m-d H:i:s');
                $targetDate = date('Y-m-d', strtotime($dateTimeStart));

                if ($eventPacificDate != $targetDate) {
                    continue;
                }

                $bodyPreview = $location['bodyPreview'] ?? null;
                $isHaulAway = strpos($bodyPreview, 'haul away') !== false;

                $displayName = $location['location']['displayName'] ?? null;
                if (!$displayName) {
                    continue;
                }

                $address = $location['location']['address'] ?? null;
                if ($address) {
                    $street = $location['location']['address']['street'] ?? null;
                    $city = $location['location']['address']['city'] ?? null;
                    $state = $location['location']['address']['state'] ?? null;
                    $postalCode = $location['location']['address']['postalCode'] ?? null;
                    $newDisplayName = $street . ', ' . $city . ', ' . $state . ', ' . $postalCode;
                    $displayName = mb_strlen($newDisplayName) > mb_strlen(
                        $displayName
                    ) ? $newDisplayName : $displayName;
                }

                $exists = MicrosoftLocation::find()
                    ->where(['displayName' => $displayName])
                    ->one();

                $microsoftLocation = $exists ?: new MicrosoftLocation();
                $microsoftLocation->date_time = $eventPacificTime;
                $microsoftLocation->addMicrosoftId($group['microsoft_id']);
                $microsoftLocation->displayName = $microsoftLocation->displayName ?: $displayName;
                if (!$microsoftLocation->lat || !$microsoftLocation->lon) {
                    $microsoftLocation = $this->geoCodeItem($microsoftLocation);
                }
                $microsoftLocation->haul_away = $isHaulAway;
                $microsoftLocation->save();
                $newLocations[] = $microsoftLocation->toArray();
            }
        }

        return $newLocations;
    }

    private function geoCodeItem(MicrosoftLocation $location): MicrosoftLocation
    {
        if (!$location->lat || !$location->lon) {
            $response = $this->getCodeByLocationv2($location->displayName);
            sleep(2);

            $location->lat = $response['lat'] ?? null;
            $location->lon = $response['lng'] ?? null;
        }
        return $location;
    }

}