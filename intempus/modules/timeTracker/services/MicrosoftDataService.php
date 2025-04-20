<?php


namespace app\modules\timeTracker\services;

use app\modules\timeTracker\models\MicrosoftGroup;
use app\modules\timeTracker\models\MicrosoftLocation;
use app\modules\timeTracker\services\interfaces\ApiInterface;
use app\modules\timeTracker\traits\CoordinateTrait;
use GuzzleHttp\Client;
use Microsoft\Graph\GraphServiceClient;
use Microsoft\Kiota\Abstractions\ApiException;
use Microsoft\Kiota\Authentication\Oauth\ClientCredentialContext;
use SebastianBergmann\CodeCoverage\Report\PHP;
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
            $exists = MicrosoftGroup::find()->where(['microsoft_id' => $group['id']])->exists();
            if (!$exists) {
                $microsoftGroup = new MicrosoftGroup();
                $microsoftGroup->name = $group['displayName'];
                $microsoftGroup->email = $group['mail'] ?? '';
                $microsoftGroup->microsoft_id = $group['id'];
                $microsoftGroup->save();
                $count++;
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
                $response = $this->getCodeByLocation($location['displayName']);

                $lat = $response[0]['lat'] ?? null;
                $lon = $response[0]['lon'] ?? null;

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
    public function groupsByNameAndDate($dateTimeStart, $dateTimeEnd): ?array
    {
        $queryParams = [
            '$select' => 'id,subject,bodyPreview,location, start, end, createdDateTime',
            '$top' => '200',
            '$filter' => "start/dateTime ge '" . $dateTimeStart . "' and start/dateTime lt '" . $dateTimeEnd . "'",
        ];

        $response = $this->apiService->requestGet('groups/ac6e55e0-20a8-4cbe-b5f5-3209d4f45056/events', $queryParams);
        $locations = $response['value'] ?? null;

        $newLocations = [];
        foreach ($locations as $location) {
            $bodyPreview = $location['bodyPreview'] ?? null;
            $isHaulAway = strpos($bodyPreview, 'haul away') !== false;

            $displayName = $location['location']['displayName'] ?? null;
            if (!$displayName) continue;

            $address = $location['location']['address'] ?? null;
            if ($address) {
                $street = $location['location']['address']['street'] ?? null;
                $city = $location['location']['address']['city'] ?? null;
                $state = $location['location']['address']['state'] ?? null;
                $postalCode = $location['location']['address']['postalCode'] ?? null;
                $newDisplayName = $street . ', ' . $city . ', ' . $state . ', ' . $postalCode;
                $displayName = mb_strlen($newDisplayName) > mb_strlen($displayName) ? $newDisplayName : $displayName;
            }
            $fullTime = $location['start']['dateTime'] ?? null;
            $fullTime = $fullTime ? (new \DateTime($fullTime))->format('Y-m-d H:i:s') : $dateTimeStart;
            $exists = MicrosoftLocation::find()->where(['displayName' => $displayName])->one();
            $object = $exists ?: new MicrosoftLocation();
            $newLocations[] = $this->saveLocation($object, $displayName, $isHaulAway, $fullTime);
        }

        return $newLocations;
    }

    /**
     * @param MicrosoftLocation|ActiveRecord $microsoftLocation
     * @param string $displayName
     * @param bool $isHaulAway
     * @param string $dateTimeStart
     * @return array
     * @throws Exception
     */
    private function saveLocation(MicrosoftLocation $microsoftLocation, string $displayName, bool $isHaulAway, string $dateTimeStart): array
    {
        $microsoftLocation->lat = 0;
        $microsoftLocation->lon = 0;
        $microsoftLocation->displayName = $displayName;
        $microsoftLocation->haul_away = $isHaulAway;
        $microsoftLocation->date_time = $dateTimeStart;
        $microsoftLocation = $this->geoCodeItem($microsoftLocation);
        $microsoftLocation->save();
        return MicrosoftLocation::find()->where(['displayName' => $displayName])->one()->toArray();
    }

    private function geoCodeItem(MicrosoftLocation $location): MicrosoftLocation
    {
//        if (!$location->lat || !$location->lon) {
        $response = $this->getCodeByLocationv2($location->displayName);
        sleep(2);
        $location->lat = $response['features'][0]['properties']['lat'] ?? null;
        $location->lon = $response['features'][0]['properties']['lon'] ?? null;
//        }
        return $location;
    }

}