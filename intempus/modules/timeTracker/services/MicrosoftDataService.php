<?php


namespace app\modules\timeTracker\services;

use app\modules\timeTracker\models\MicrosoftGroup;
use app\modules\timeTracker\models\MicrosoftLocation;
use app\modules\timeTracker\services\interfaces\ApiInterface;
use app\modules\timeTracker\traits\CoordinateTrait;
use GuzzleHttp\Client;

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
            } catch (\Exception $e) {}
        }
        return $count;
    }

}