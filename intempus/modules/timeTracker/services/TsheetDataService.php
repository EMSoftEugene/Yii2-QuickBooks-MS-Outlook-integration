<?php


namespace app\modules\timeTracker\services;

use app\modules\timeTracker\models\MicrosoftGroup;
use app\modules\timeTracker\models\MicrosoftLocation;
use app\modules\timeTracker\models\TsheetGeolocation;
use app\modules\timeTracker\models\TsheetUser;
use app\modules\timeTracker\services\interfaces\ApiInterface;
use GuzzleHttp\Client;

class TsheetDataService
{
    private ApiInterface $apiService;

    public function __construct()
    {
        $this->apiService = new TsheetService();
    }

    public function getAllUsers(): ?array
    {
        $queryParams = [
        ];
        $response = $this->apiService->requestGet('users', $queryParams);
        $responseUsers = $response['results']['users'] ?? [];

        $users = [];
        foreach ($responseUsers as $user) {
            $users[] = [
                'first_name' => $user['first_name'],
                'last_name' => $user['last_name'],
                'email' => $user['email'],
            ];
        }
        return $users;
    }

    public function getUsers(): ?array
    {
        $microsoftGroup = MicrosoftGroup::getTechNames();
        $queryParams = [
        ];
        $response = $this->apiService->requestGet('users', $queryParams);
        $responseUsers = $response['results']['users'] ?? [];

        $users = [];
        foreach ($microsoftGroup as $user) {

            $found = array_filter($responseUsers,
                function ($item) use ($user) {
                    return $item['first_name'] == $user['first_name'] && $item['last_name'] == $user['last_name'];
                });

            if ($found) {
                $users = array_merge($users, $found);
            }

        }
        return $users;
    }

    public function saveNewUsers(array $users): int
    {
        $count = 0;
        foreach ($users as $user) {
            $exists = TsheetUser::find()->where(['external_id' => $user['id']])->exists();
            if (!$exists) {
                $tsheetUser = new TsheetUser();
                $tsheetUser->first_name = $user['first_name'];
                $tsheetUser->last_name = $user['last_name'];
                $tsheetUser->email = $user['email'];
                $tsheetUser->external_id = $user['id'];
                $tsheetUser->save();
                $count++;
            }
        }
        return $count;
    }

    public function getGeolocations($date = null): ?array
    {
        $date = $date ? new \DateTime($date) : new \DateTime();
        $date->modify('-1 days');
        $startDate = $date->format('Y-m-d') . ' 00:00:00'; // ISO 8601 format (YYYY-MM-DDThh:mm:ss±hh:mm)
        $endDate = $date->format('Y-m-d') . ' 23:59:59';
        $formatedStartDate = (new \DateTime($startDate))->format('c');
        $formatedEndDate = (new \DateTime($endDate))->format('c');

        $users = TsheetUser::find()->select('external_id')->asArray()->all();
        $userIds = '';
        foreach ($users as $user) {
            $userIds .= $user['external_id'] . ',';
        }

        $result = [];
        $page = 1;
        $count = 200;
        while ($count == 200) {
            $queryParams = [
                'user_ids' => $userIds,
                'modified_since' => $formatedStartDate,
                'modified_before' => $formatedEndDate,
                'page' => $page,
                'limit' => 200
            ];
            $response = $this->apiService->requestGet('geolocations', $queryParams);
            $responseGeo = $response['results']['geolocations'] ?? [];
            $result = array_merge($result, $responseGeo);

            $count = $responseGeo ? count($responseGeo) : 0;

            $page++;
            sleep(1);
        }

        return $result;
    }

    public function saveNewGeolocations(array $geolocations): int
    {
        $count = 0;
        foreach ($geolocations as $geolocation) {
            $exists = TsheetGeolocation::find()->where(['tsheet_id' => $geolocation['id']])->exists();
            if (!$exists) {
                $tsheetGeolocation = new TsheetGeolocation();
                $tsheetGeolocation->tsheet_user_id = $geolocation['user_id'];
                $tsheetGeolocation->tsheet_id = $geolocation['id'];
                $tsheetGeolocation->lat = $geolocation['latitude'];
                $tsheetGeolocation->lon = $geolocation['longitude'];
                $tsheetGeolocation->speed = $geolocation['speed'];
                $tsheetGeolocation->tsheet_created = $geolocation['created'];
                $tsheetGeolocation->save();
                $count++;
            }
        }
        return $count;
    }

    public function getUserGeolocations($userId, $dateStart, $dateEnd): ?array
    {
        $formatedStartDate = (new \DateTime($dateStart))->format('c');
        $formatedEndDate = (new \DateTime($dateEnd))->format('c');

        $result = [];
        $page = 1;
        $count = 200;
        while ($count == 200) {
            $queryParams = [
                'user_ids' => $userId,
                'modified_since' => $formatedStartDate,
                'modified_before' => $formatedEndDate,
                'page' => $page,
                'limit' => 200
            ];
            $response = $this->apiService->requestGet('geolocations', $queryParams);
            $responseGeo = $response['results']['geolocations'] ?? [];
            $result = array_merge($result, $responseGeo);

            $count = $responseGeo ? count($responseGeo) : 0;

            $page++;
        }

        return $result;
    }

}