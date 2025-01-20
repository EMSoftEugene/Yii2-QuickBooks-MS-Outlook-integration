<?php


namespace app\modules\timeTracker\services;

use app\models\VehiclesHistory;
use app\modules\timeTracker\helper\DateTimeHelper;
use app\modules\timeTracker\services\interfaces\ApiInterface;
use app\modules\timeTracker\traits\CoordinateTrait;
use GuzzleHttp\Client;

class VerizonDataService
{
    use CoordinateTrait;

    private ApiInterface $apiService;

    public function __construct()
    {
        $this->apiService = new VerizonService();
    }

    public function getVehiclesHistory($vehiclenumber, $startdatetimeutc, $enddatetimeutc): ?array
    {
        $queryParams = [
            'startdatetimeutc' => $startdatetimeutc,
            'enddatetimeutc' => $enddatetimeutc,
        ];
        $url = 'rad/v1/vehicles/'.$vehiclenumber.'/status/history';

        $response = $this->apiService->requestGet($url, $queryParams);

        return $response;
    }

    public function saveNewHistories($histories): int
    {
        $count = 0;
        foreach ($histories as $history) {
            $historyDate = DateTimeHelper::applyTimeZone($history['UpdateUtc']);
            $exists = VehiclesHistory::find()
                ->where(['VehicleNumber' => $history['VehicleNumber']])
                ->andWhere(['UpdateUtc' => $historyDate])
                ->exists();
            if (!$exists) {
                $vehiclesHistory = new VehiclesHistory();
                $vehiclesHistory->VehicleNumber = $history['VehicleNumber'];
                $vehiclesHistory->VehicleName = $history['VehicleName'];
                $vehiclesHistory->UpdateUtc = $historyDate;
                $vehiclesHistory->IsPrivate = $history['IsPrivate'];
                $vehiclesHistory->DriverNumber = $history['DriverNumber'];
                $vehiclesHistory->FirstName = $history['FirstName'];
                $vehiclesHistory->LastName = $history['LastName'];
                $vehiclesHistory->AddressLine1 = $history['Address']['AddressLine1'];
                $vehiclesHistory->AddressLine2 = $history['Address']['AddressLine2'];
                $vehiclesHistory->Locality = $history['Address']['Locality'];
                $vehiclesHistory->AdministrativeArea = $history['Address']['AdministrativeArea'];
                $vehiclesHistory->PostalCode = $history['Address']['PostalCode'];
                $vehiclesHistory->Country = $history['Address']['Country'];
                $vehiclesHistory->Latitude = $history['Latitude'];
                $vehiclesHistory->Longitude = $history['Longitude'];
                $vehiclesHistory->Speed = $history['Speed'];
                $vehiclesHistory->BatteryLevel = $history['BatteryLevel'];
                $vehiclesHistory->TractionBatteryChargingLastStartUtc = $history['TractionBatteryChargingLastStartUtc'];
                $vehiclesHistory->TractionBatteryChargingUtc = $history['TractionBatteryChargingUtc'];

                $vehiclesHistory->location = $history['Address']['AddressLine1'];
                $vehiclesHistory->save();
                $count++;
            }
        }
        return $count;
    }

}