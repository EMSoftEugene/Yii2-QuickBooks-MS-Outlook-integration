<?php

namespace app\modules\timeTracker\services;

use app\modules\timeTracker\models\VehiclesHistory;
use app\modules\timeTracker\models\MicrosoftGroup;
use app\modules\timeTracker\models\MicrosoftLocation;
use app\modules\timeTracker\models\TimeTracker;
use app\modules\timeTracker\traits\CoordinateTrait;

class TimeTrackerV2Service
{
    use CoordinateTrait;

    const DEFAULT_TIME = 1;
    const PLACE_STATUS_START = 'start';
    const PLACE_STATUS_END = 'end';
    const MIN_DURATION_MINUTES = 5;

    private array $params;
    private string $placeStatus = '';

    public function __construct()
    {
        $module = \Yii::$app->getModule('timeTracker');
        $this->params = $module->params;
    }

    public function create($date): int
    {
        $fullDate = $date;
        $date = (new \DateTime($fullDate))->format('Y-m-d');
        $dateNext = (new \DateTime($date))->modify('+1 day')->format('Y-m-d');

        $count = 0;
        $startDate = $date . ' 00:00:00';
        $endDate = $date . ' 23:59:59';
        $userIds = VehiclesHistory::find()
            ->select('VehicleNumber')
            ->where(['between', 'UpdateUtc', $startDate, $endDate])
            ->groupBy('VehicleNumber')
            ->column();

        foreach ($userIds as $userId) {
            $places = [];
            $placeIndex = -1;
            $microsoftUser = MicrosoftGroup::find()->where(['verizon_id' => $userId])->one();
            if (!$microsoftUser) {
                continue;
            }

            $rows = VehiclesHistory::find()
                ->where(['VehicleNumber' => $userId])
                ->andWhere(['between', 'UpdateUtc', $startDate, $endDate])
                ->orderBy('UpdateUtc ASC')
                ->all();

            echo "countRows=" . count($rows) . PHP_EOL;

            $tmp = [];
            foreach ($rows as $key => $row) {
                $tmp[] = $row->toArray();
            }
//            file_put_contents('timeTracker3.json', print_r((array)$tmp, true));

            foreach ($rows as $key => $row) {
                $nextRow = $rows[$key + 1] ?? null;

                if ($key == 0) {
                    if ((int)$row->Speed == 0) {
                        $placeIndex++;
                        $places = $this->startPlace($places, $placeIndex, $row, $microsoftUser);
                        $this->addAddressToPlace($places, $placeIndex, $row);
                    }
                    continue;
                }

                if ($this->placeStatus == '') {
                    if ((int)$row->Speed == 0) {
                        $placeIndex++;
                        $places = $this->startPlace($places, $placeIndex, $row, $microsoftUser);
                        $this->addAddressToPlace($places, $placeIndex, $row);
                    }
                    continue;
                }

                if ($this->placeStatus == self::PLACE_STATUS_START) {
                    if ((int)$row->Speed == 0) {
                        $this->addAddressToPlace($places, $placeIndex, $row);
                    }

                    if ($nextRow) {
                        $startLat = (float)$places[$placeIndex]['start']['Latitude'];
                        $startLon = (float)$places[$placeIndex]['start']['Longitude'];

                        $currentTime = new \DateTime($row->UpdateUtc);
                        $nextTime = new \DateTime($nextRow->UpdateUtc);
                        $diff = $currentTime->diff($nextTime);
                        $minutesDiff = $diff->i + ($diff->h * 60) + ($diff->days * 24 * 60);

                        if ($minutesDiff > 10) {
                            $places = $this->endPlace($places, $placeIndex, $nextRow);
                        } else {
                            $distance = $this->getDistance(
                                (float)$nextRow->Latitude,
                                (float)$nextRow->Longitude,
                                $startLat,
                                $startLon
                            );

                            if (((int)$row->Speed != 0 && (int)$nextRow->Speed != 0 && $distance > $this->params['distance'])
                                || ((int)$nextRow->Speed > 0 && $distance > $this->params['distance'])) {
                                if (isset($places[$placeIndex])) {
                                    $places = $this->endPlace($places, $placeIndex, $row);
                                }
                            }
                        }
                    } else {
                        if (isset($places[$placeIndex])) {
                            $places = $this->endPlace($places, $placeIndex, $row);
                        }
                    }
                }
            }

            if ($this->placeStatus == self::PLACE_STATUS_START && isset($places[$placeIndex])) {
                $lastRow = end($rows);
                if ($lastRow) {
                    $places = $this->endPlace($places, $placeIndex, $lastRow);
                }
            }

            if ($places) {
                $places = $this->filterPlaces($places);
//                file_put_contents('timeTracker.json', json_encode($places, JSON_PRETTY_PRINT));
                $places = $this->calculatedPlaceFix($places);
//                file_put_contents('timeTracker2.json', json_encode($places, JSON_PRETTY_PRINT));
//                die;
                $places = $this->fixToRealMicrosoftLocations($places, $date, $dateNext, $microsoftUser);
                $count += count($places);
                $this->saveTimeTracker($places);
            }
        }
        return $count;
    }

    private function calculatedPlaceFix(array $places): array
    {
        if (count($places) < 2) {
            return $places;
        }
        return $this->mergeMicrosoftLocationsWithIntermediates($places);
    }

    private function mergeMicrosoftLocationsWithIntermediates(array $places): array
    {
        $microsoftIndexes = [];
        foreach ($places as $index => $place) {
            if ($place['isMicrosoftLocation']) {
                $microsoftIndexes[$index] = $place;
            }
        }

        if (count($microsoftIndexes) < 2) {
            return $places;
        }

        $msIndexes = array_keys($microsoftIndexes);
        $mergedPlaces = [];
        $skipIndexes = [];

        for ($i = 0; $i < count($msIndexes); $i++) {
            $currentIdx = $msIndexes[$i];
            if (in_array($currentIdx, $skipIndexes)) {
                continue;
            }

            $currentStop = $places[$currentIdx];
            $mergedStop = $currentStop;
            $mergedWithAny = false;

            for ($j = $i + 1; $j < count($msIndexes); $j++) {
                $nextIdx = $msIndexes[$j];
                if (in_array($nextIdx, $skipIndexes)) {
                    continue;
                }

                $nextStop = $places[$nextIdx];
                if ($currentStop['locationName'] === $nextStop['locationName']) {
                    $distance = $this->getDistanceBetweenStops($currentStop, $nextStop);
                    if ($distance <= $this->params['distance']) {
                        $mergedStop = $this->mergeStopTimes($mergedStop, $nextStop);
                        for ($k = $currentIdx + 1; $k < $nextIdx; $k++) {
                            if (!in_array($k, $skipIndexes)) {
                                $skipIndexes[] = $k;
                            }
                        }
                        $skipIndexes[] = $nextIdx;
                        $mergedWithAny = true;
                        $currentStop = $mergedStop;
                    } else {
                        break;
                    }
                } else {
                    break;
                }
            }

            $mergedPlaces[] = $mergedStop;
            $skipIndexes[] = $currentIdx;
        }

        foreach ($places as $index => $place) {
            if (!in_array($index, $skipIndexes)) {
                $mergedPlaces[] = $place;
            }
        }

        usort($mergedPlaces, function ($a, $b) {
            return strtotime($a['start']['UpdateUtc']) <=> strtotime($b['start']['UpdateUtc']);
        });

        return $mergedPlaces;
    }

    private function mergeStopTimes(array $stop1, array $stop2): array
    {
        $start1 = new \DateTime($stop1['start']['UpdateUtc']);
        $start2 = new \DateTime($stop2['start']['UpdateUtc']);
        $end1 = new \DateTime($stop1['end']['UpdateUtc']);
        $end2 = new \DateTime($stop2['end']['UpdateUtc']);

        $mergedStart = $start1 < $start2 ? $stop1['start'] : $stop2['start'];
        $mergedEnd = $end1 > $end2 ? $stop1['end'] : $stop2['end'];

        $newStart = new \DateTime($mergedStart['UpdateUtc']);
        $newEnd = new \DateTime($mergedEnd['UpdateUtc']);

        $diff = $newStart->diff($newEnd);
        $totalMinutes = $diff->days * 24 * 60 + $diff->h * 60 + $diff->i;

        $h = floor($totalMinutes / 60);
        $i = $totalMinutes % 60;

        $h = str_pad($h, 2, '0', STR_PAD_LEFT);
        $i = str_pad($i, 2, '0', STR_PAD_LEFT);
        $duration = $h . ':' . $i;

        $mergedStop = $stop1;
        $mergedStop['start'] = $mergedStart;
        $mergedStop['end'] = $mergedEnd;
        $mergedStop['duration'] = $duration;
        $mergedStop['clock_in'] = (new \DateTime($mergedStart['UpdateUtc']))->format('h:i:s A');
        $mergedStop['clock_out'] = (new \DateTime($mergedEnd['UpdateUtc']))->format('h:i:s A');

        return $mergedStop;
    }

    private function getDistanceBetweenStops(array $stop1, array $stop2): float
    {
        $lat1 = (float)$stop1['start']['Latitude'];
        $lon1 = (float)$stop1['start']['Longitude'];
        $lat2 = (float)$stop2['start']['Latitude'];
        $lon2 = (float)$stop2['start']['Longitude'];

        return $this->getDistance($lat1, $lon1, $lat2, $lon2);
    }

    private function addAddressToPlace(&$places, $placeIndex, $row)
    {
        if (!isset($places[$placeIndex])) {
            return;
        }

        if (!isset($places[$placeIndex]['addresses'])) {
            $places[$placeIndex]['addresses'] = [];
            $places[$placeIndex]['addressesDetails'] = [];
        }

        $addressId = $this->generateAddressId($row);

        if (!in_array($addressId, $places[$placeIndex]['addresses'])) {
            $places[$placeIndex]['addresses'][] = $addressId;
            $addressDetails = [
                'time' => $row->UpdateUtc,
                'latitude' => $row->Latitude,
                'longitude' => $row->Longitude,
                'address' => $this->getFormattedAddress($row),
                'speed' => $row->Speed
            ];
            $places[$placeIndex]['addressesDetails'][] = $addressDetails;
        } else {
            $lastIndex = array_search($addressId, $places[$placeIndex]['addresses']);
            if ($lastIndex !== false && isset($places[$placeIndex]['addressesDetails'][$lastIndex])) {
                $places[$placeIndex]['addressesDetails'][$lastIndex]['time'] = $row->UpdateUtc;
            }
        }
    }

    private function startPlace($places, $placeIndex, $row, $user): array
    {
        $this->placeStatus = self::PLACE_STATUS_START;

        if (!isset($places[$placeIndex]['start'])) {
            $places[$placeIndex]['start'] = $row->getAttributes();
            $places[$placeIndex]['user_id'] = $user->microsoft_id;
            $places[$placeIndex]['user'] = $user->name;
            $date = new \DateTime($places[$placeIndex]['start']['UpdateUtc']);
            $places[$placeIndex]['date'] = $date->format('Y-m-d H:i:s');
            $places[$placeIndex]['clock_in'] = $date->format('h:i:s A');
            $places[$placeIndex]['addresses'] = [];
            $places[$placeIndex]['addressesDetails'] = [];

            $places[$placeIndex]['isMicrosoftLocation'] = false;
            $places[$placeIndex]['locationName'] = '';
            $places[$placeIndex]['locationNameVerizon'] = '';
            $places[$placeIndex]['haul_away'] = false;

            $this->addAddressToPlace($places, $placeIndex, $row);
        }

        return $places;
    }

    private function endPlace($places, $placeIndex, $row): array
    {
        $this->placeStatus = "";
        $places[$placeIndex]['end'] = $row->getAttributes();

        $this->checkAllAddressesForMicrosoftLocation($places, $placeIndex);

        $startDate = (new \DateTime($places[$placeIndex]['start']['UpdateUtc']))->format('Y-m-d H:i:s');
        $startDate = new \DateTime($startDate);
        $endDate = (new \DateTime($places[$placeIndex]['end']['UpdateUtc']))->format('Y-m-d H:i:s');
        $endDate = new \DateTime($endDate);

        $diff = $startDate->diff($endDate);
        $i = $diff->i ? $diff->i + round($diff->s / 60) : 0;

        $h = str_pad($diff->h, 2, '0', STR_PAD_LEFT);
        $i = str_pad($i, 2, '0', STR_PAD_LEFT);
        $duration = $h . ':' . $i;
        $places[$placeIndex]['duration'] = $duration;
        $places[$placeIndex]['clock_out'] = $endDate->format('h:i:s A');

        return $places;
    }

    private function checkAllAddressesForMicrosoftLocation(&$places, $placeIndex): void
    {
        if (!isset($places[$placeIndex]['addressesDetails']) || empty($places[$placeIndex]['addressesDetails'])) {
            $this->setDefaultLocationInfo($places, $placeIndex);
            return;
        }

        $bestLocation = null;
        $bestDistance = PHP_FLOAT_MAX;
        $defaultLocationSet = false;

        foreach ($places[$placeIndex]['addressesDetails'] as $addressDetail) {
            $geoPlace = $this->checkGeoCodePlaceFromDetails($addressDetail, $places[$placeIndex]['user_id']);

            if ($geoPlace['isMicrosoftLocation']) {
                if ($geoPlace['distance'] < $bestDistance) {
                    $bestDistance = $geoPlace['distance'];
                    $bestLocation = $geoPlace;
                }
            }

            if (!$defaultLocationSet) {
                $places[$placeIndex]['locationNameVerizon'] = $addressDetail['address'] ??
                    'GPS: ' . $addressDetail['latitude'] . ', ' . $addressDetail['longitude'];
                $defaultLocationSet = true;
            }
        }

        if ($bestLocation !== null) {
            $places[$placeIndex]['isMicrosoftLocation'] = true;
            $places[$placeIndex]['locationName'] = $bestLocation['locationName'];
            $places[$placeIndex]['haul_away'] = $bestLocation['haul_away'];
        } else {
            $this->setDefaultLocationInfo($places, $placeIndex);
        }
    }

    private function checkGeoCodePlaceFromDetails(array $addressDetail, string $microsoftUserId): array
    {
        $date = (new \DateTime($addressDetail['time']))->format('Y-m-d');
        $dateNext = (new \DateTime($addressDetail['time']))->modify('+1 day')->format('Y-m-d');

        $isMicrosoftLocation = false;
        $haul_away = false;
        $locationName = $addressDetail['address'] ??
            'GPS: ' . $addressDetail['latitude'] . ', ' . $addressDetail['longitude'];
        $distance = PHP_FLOAT_MAX;

        $locations = MicrosoftLocation::find()
            ->where(['>=', 'date_time', $date])
            ->andWhere(['<', 'date_time', $dateNext])
            ->andWhere(['microsoft_id' => $microsoftUserId])
            ->all();

        $bestMatch = null;
        $bestDistance = PHP_FLOAT_MAX;

        foreach ($locations as $location) {
            if (!$location->lat || !$location->lon) {
                sleep(1);
                $response = $this->getCodeByLocationv2($location['displayName']);
                $responseLat = $response['lat'] ?? null;
                $responseLon = $response['lng'] ?? null;
                if ($responseLat && $responseLon) {
                    $location->lat = $responseLat;
                    $location->lon = $responseLon;
                    $location->save();
                }
                if (!$location->lat || !$location->lon) {
                    continue;
                }
            }

            $currentDistance = $this->getDistance(
                (float)$location->lat,
                (float)$location->lon,
                $addressDetail['latitude'],
                $addressDetail['longitude']
            );

            if ($currentDistance < $this->params['distance'] && $currentDistance < $bestDistance) {
                $bestDistance = $currentDistance;
                $bestMatch = $location;
            }
        }

        if ($bestMatch !== null) {
            $isMicrosoftLocation = true;
            $locationName = $bestMatch->displayName;
            $haul_away = $bestMatch->haul_away;
            $distance = $bestDistance;
        }

        return [
            'isMicrosoftLocation' => $isMicrosoftLocation,
            'locationName' => $locationName,
            'haul_away' => $haul_away,
            'distance' => $distance
        ];
    }

    private function setDefaultLocationInfo(&$places, $placeIndex): void
    {
        // Используем первый адрес для locationNameVerizon
        if (isset($places[$placeIndex]['addressesDetails'][0])) {
            $firstAddress = $places[$placeIndex]['addressesDetails'][0];
            $places[$placeIndex]['locationNameVerizon'] = $firstAddress['address'] ??
                'GPS: ' . $firstAddress['latitude'] . ', ' . $firstAddress['longitude'];
        } else {
            // Если нет адресов, используем стартовую точку
            $start = $places[$placeIndex]['start'];
            $addressParts = array_filter([
                $start['AddressLine1'] ?? '',
                $start['AddressLine2'] ?? '',
                $start['Locality'] ?? '',
                $start['AdministrativeArea'] ?? '',
                $start['PostalCode'] ?? ''
            ]);
            $places[$placeIndex]['locationNameVerizon'] = !empty($addressParts)
                ? implode(', ', $addressParts)
                : 'GPS: ' . $start['Latitude'] . ', ' . $start['Longitude'];
        }

        $places[$placeIndex]['isMicrosoftLocation'] = false;
        $places[$placeIndex]['locationName'] = $places[$placeIndex]['locationNameVerizon'];
        $places[$placeIndex]['haul_away'] = false;
    }


    private function generateAddressId($row): string
    {
        $parts = [
            round($row->Latitude, 6),
            round($row->Longitude, 6),
            $row->AddressLine1 ?? '',
            $row->AddressLine2 ?? '',
            $row->Locality ?? '',
            $row->PostalCode ?? ''
        ];

        return md5(implode('|', $parts));
    }

    private function getFormattedAddress($row): string
    {
        $addressParts = array_filter([
            $row->AddressLine1 ?? '',
            $row->AddressLine2 ?? '',
            $row->Locality ?? '',
            $row->AdministrativeArea ?? '',
            $row->PostalCode ?? ''
        ]);

        if (!empty($addressParts)) {
            return implode(', ', $addressParts);
        }

        return 'GPS: ' . round($row->Latitude, 6) . ', ' . round($row->Longitude, 6);
    }

    private function checkGeoCodePlace($place, $microsoftUser)
    {
        $date = (new \DateTime($place['UpdateUtc']))->format('Y-m-d');
        $dateNext = (new \DateTime($place['UpdateUtc']))->modify('+1 day')->format('Y-m-d');
        $isMicrosoftLocation = false;
        $haul_away = false;

        $locationNameVerizon = $place['location'];
        if (empty($locationNameVerizon)) {
            $addressParts = array_filter([
                $place['AddressLine1'] ?? '',
                $place['AddressLine2'] ?? '',
                $place['Locality'] ?? '',
                $place['AdministrativeArea'] ?? '',
                $place['PostalCode'] ?? ''
            ]);
            $locationNameVerizon = !empty($addressParts)
                ? implode(', ', $addressParts)
                : 'GPS: ' . $place['Latitude'] . ', ' . $place['Longitude'];
        }

        $locationName = $locationNameVerizon;

        $locations = MicrosoftLocation::find()
            ->where(['>=', 'date_time', $date])
            ->andWhere(['<', 'date_time', $dateNext])
            ->andWhere(['microsoft_id' => $microsoftUser->microsoft_id])
            ->all();

        $bestMatch = null;
        $bestDistance = PHP_FLOAT_MAX;

        foreach ($locations as $location) {
            if (!$location->lat || !$location->lon) {
                sleep(1);
                $response = $this->getCodeByLocationv2($location['displayName']);
                $responseLat = $response['lat'] ?? null;
                $responseLon = $response['lng'] ?? null;
                if ($responseLat && $responseLon) {
                    $location->lat = $responseLat;
                    $location->lon = $responseLon;
                    $location->save();
                }
                if (!$location->lat || !$location->lon) {
                    continue;
                }
            }

            $distance = $this->getDistance(
                (float)$location->lat,
                (float)$location->lon,
                $place['Latitude'],
                $place['Longitude']
            );

            if ($distance < $this->params['distance'] && $distance < $bestDistance) {
                $bestDistance = $distance;
                $bestMatch = $location;
            }
        }

        if ($bestMatch !== null) {
            $isMicrosoftLocation = true;
            $locationName = $bestMatch->displayName;
            $haul_away = $bestMatch->haul_away;
        }

        return compact('isMicrosoftLocation', 'locationName', 'haul_away', 'locationNameVerizon');
    }

    public function fixToRealMicrosoftLocations($places, $date, $dateNext, $microsoftUser)
    {
        $allMicrosoftLocations = [];
        foreach ($places as $place) {
            if ($place['isMicrosoftLocation']) {
                $allMicrosoftLocations[$place['locationName']] = ['location' => $place['locationName']];
            }
        }

        $ext = [];
        $locations = MicrosoftLocation::find()
            ->where(['>=', 'date_time', $date])
            ->andWhere(['microsoft_id' => $microsoftUser->microsoft_id])
            ->andWhere(['<', 'date_time', $dateNext])
            ->orderBy('date_time', SORT_ASC)
            ->asArray()
            ->all();

        foreach ($locations as $key => $location) {
            if (!isset($allMicrosoftLocations[$location['displayName']])) {
                $location['key'] = $key;
                $ext[] = $location;
            }
        }

        if ($ext) {
            foreach ($ext as $extKey => $item) {
                $newPlaces = [];
                $tmpCount = [];
                $neededKey = $item['key'];
                $placeKey = 0;
                $clonePlaces = $places;
                $added = false;

                foreach ($places as $key => $place) {
                    $clock_out = null;
                    if ($neededKey == 0) {
                        $clock_out = date('h:i:s A', strtotime('-10 minutes', strtotime($place['clock_in'])));
                    }

                    if ($neededKey == $placeKey && !$clock_out) {
                        $clock_out = date('h:i:s A', strtotime('+10 minutes', strtotime($places[$key]['clock_out'])));
                        $myLastElement = end($newPlaces);
                        if (isset($myLastElement['clock_in']) && $myLastElement['clock_in'] == $clock_out) {
                            $clock_out = date(
                                'h:i:s A',
                                strtotime('+60 minutes', strtotime($myLastElement['clock_out']))
                            );
                        }
                    }

                    if ($clock_out && !$added) {
                        $lastPlace = true;
                        if ($neededKey != 0) {
                            foreach ($clonePlaces as $subKey => $subPlace) {
                                if ($subKey <= $key) {
                                    continue;
                                }
                                if ($subPlace['locationName'] === $place['locationName']) {
                                    $lastPlace = false;
                                    break;
                                }
                            }
                        }

                        if ($lastPlace) {
                            $clock_in = date('h:i:s A', strtotime('+10 minutes', strtotime($clock_out)));
                            $tmp = [
                                'start' => [''],
                                'date' => $date,
                                'locationName' => $item['displayName'],
                                'clock_in' => $clock_out,
                                'clock_out' => $clock_in,
                                'duration' => '00:10',
                                'isMicrosoftLocation' => true,
                                'locationNameVerizon' => '',
                                'haul_away' => $item['haul_away'],
                                'user_id' => $microsoftUser->microsoft_id,
                                'user' => $microsoftUser->name,
                                'end' => [''],
                                'addresses' => [],
                                'addressesDetails' => []
                            ];

                            $newPlaces[] = $tmp;
                            $added = true;
                            $newPlaces[] = $place;
                            $tmpCount = [];
                            foreach ($newPlaces as $subPlace) {
                                if ($subPlace['isMicrosoftLocation']) {
                                    $tmpCount[$subPlace['locationName']] = $subPlace['locationName'];
                                }
                            }
                            $placeKey = count($tmpCount);
                            continue;
                        }
                    }

                    $newPlaces[] = $place;
                    if ($place['isMicrosoftLocation']) {
                        $tmpCount[$place['locationName']] = $place['locationName'];
                        $placeKey = count($tmpCount);
                    }
                }

                $places = $newPlaces;
            }
        }
        return $places;
    }

    private function saveTimeTracker(array $places): void
    {
        foreach ($places as $place) {
            $clock_in = (new \DateTime($place['clock_in']))->format('H:i:s');
            $clock_out = (new \DateTime($place['clock_out']))->format('H:i:s');
            $date = (new \DateTime($place['date']))->format('Y-m-d');
            $exists = TimeTracker::find()
                ->where(['user_id' => $place['user_id']])
                ->andWhere(['clock_in' => $clock_in])
                ->andWhere(['date' => $date])
                ->exists();
            if (!$exists) {
                $timeTracker = new TimeTracker();
                $timeTracker->isMicrosoftLocation = $place['isMicrosoftLocation'];
                $timeTracker->locationName = $place['locationName'];
                if (!isset($place['locationNameVerizon'])) {
                    $place['locationNameVerizon'] = '';
                }
                $timeTracker->locationNameVerizon = $place['locationNameVerizon'];
                $timeTracker->date = $place['date'];
                $timeTracker->clock_in = $clock_in;
                $timeTracker->clock_out = $clock_out;
                $timeTracker->duration = $place['duration'];
                $timeTracker->user_id = $place['user_id'];
                $timeTracker->user = $place['user'];
                $timeTracker->haul_away = (bool)$place['haul_away'];
                $timeTracker->save();
            }
        }
    }

    private function filterPlaces($places): array
    {
        $result = [];
        foreach ($places as $place) {
            if ($place['duration'] == '00:00') {
                continue;
            }

            $explode = explode(':', $place['duration']);
            $hours = (int)$explode[0];
            $minutes = (int)$explode[1];
            $totalMinutes = ($hours * 60) + $minutes;

            if (
                (
                    (int)$explode[0] > 0) ||
                    ((int)$explode[1] - self::DEFAULT_TIME > 0) && ($totalMinutes >= self::MIN_DURATION_MINUTES)) {
                $result[] = $place;
            }
    }
        return $result;
    }
}