<?php

namespace app\modules\timeTracker\traits;

use GuzzleHttp\Client;

trait CoordinateTrait
{

    /**
     * Calculates the great-circle distance between two points, with
     * the Vincenty formula.
     * @param float $latitudeFrom Latitude of start point in [deg decimal]
     * @param float $longitudeFrom Longitude of start point in [deg decimal]
     * @param float $latitudeTo Latitude of target point in [deg decimal]
     * @param float $longitudeTo Longitude of target point in [deg decimal]
     * @param float $earthRadius Mean earth radius in [m]
     * @return float Distance between points in [m] (same as earthRadius)
     */
    public function getDistance(float $latitudeFrom, float $longitudeFrom, float $latitudeTo, float $longitudeTo, $earthRadius = 6371000)
    {
        // convert from degrees to radians
        $latFrom = deg2rad($latitudeFrom);
        $lonFrom = deg2rad($longitudeFrom);
        $latTo = deg2rad($latitudeTo);
        $lonTo = deg2rad($longitudeTo);
        $lonDelta = $lonTo - $lonFrom;
        $a = pow(cos($latTo) * sin($lonDelta), 2) +
            pow(cos($latFrom) * sin($latTo) - sin($latFrom) * cos($latTo) * cos($lonDelta), 2);
        $b = sin($latFrom) * sin($latTo) + cos($latFrom) * cos($latTo) * cos($lonDelta);
        $angle = atan2(sqrt($a), $b);
        return $angle * $earthRadius;
    }

    public function getCodeByLocation(string $name)
    {
        sleep(1); // 429 Too Many Requests
        $module = \Yii::$app->getModule('timeTracker');
        $client = new Client();
        $url = 'https://geocode.maps.co/search?q=' . $name . '&api_key='.$module->params['geocode_api'];
        $response = $client->request('GET', $url);
        $response = json_decode($response->getBody()->getContents(), true);
        return $response;
    }

    public function getLocationByCode(string $lat, string $lon)
    {
        sleep(1); // 429 Too Many Requests
        $module = \Yii::$app->getModule('timeTracker');
        $client = new Client();
        $url = 'https://geocode.maps.co/reverse?lat=' . $lat . '&lon=' . $lon . '&api_key='.$module->params['geocode_api'];
        $response = $client->request('GET', $url);
        $response = json_decode($response->getBody()->getContents(), true);
        $response = $response['display_name'] ?? '';
        return $response;
    }

}