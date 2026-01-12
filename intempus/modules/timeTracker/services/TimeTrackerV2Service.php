<?php


namespace app\modules\timeTracker\services;

use app\modules\timeTracker\helper\DateTimeHelper;
use app\modules\timeTracker\models\VehiclesHistory;
use app\modules\timeTracker\models\MicrosoftGroup;
use app\modules\timeTracker\models\MicrosoftLocation;
use app\modules\timeTracker\models\TimeTracker;
use app\modules\timeTracker\models\TsheetGeolocation;
use app\modules\timeTracker\models\TsheetUser;
use app\modules\timeTracker\traits\CoordinateTrait;
use SebastianBergmann\CodeCoverage\Report\PHP;

class TimeTrackerV2Service
{
    use CoordinateTrait;

    const DEFAULT_TIME = 1;
    const PLACE_STATUS_START = 'start';
    const PLACE_STATUS_END = 'end';
    private array $params;
    private string $placeStatus = '';

    public function __construct()
    {
        $module = \Yii::$app->getModule('timeTracker');
        $this->params = $module->params;
    }

    private function log($message, $data = null)
    {
        $logFile = \Yii::getAlias('@runtime/logs/timetracker_v2_debug.log');
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] $message";
        if ($data !== null) {
            $logMessage .= "\n" . print_r($data, true);
        }
        $logMessage .= "\n" . str_repeat('-', 80) . "\n";
        file_put_contents($logFile, $logMessage, FILE_APPEND);
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
            $placeIndex = 0;
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

            foreach ($rows as $key => $row) {
                $nextRow = $rows[$key + 1] ?? null;


                if ($nextRow) {
                    if ($key == 0) {
                        if ((int)$row->Speed == 0) {
                            $places = $this->startPlace($places, $placeIndex, $nextRow, $microsoftUser);
                            continue;
                        } else {
                            continue;
                        }
                    }

                    if ($this->placeStatus == '') {
                        if ((int)$row->Speed == 0 && $nextRow->Speed == 0) {
                            $places = $this->startPlace($places, $placeIndex, $nextRow, $microsoftUser);
                            continue;
                        } else {
                            continue;
                        }
                    }

                    if ($this->placeStatus == self::PLACE_STATUS_START) {
                        $startLat = (float)$places[$placeIndex]['start']['Latitude'];
                        $startLon = (float)$places[$placeIndex]['start']['Longitude'];

                        $distance = $this->getDistance(
                            (float)$nextRow->Latitude,
                            (float)$nextRow->Longitude,
                            $startLat,
                            $startLon
                        );

                        if ((int)$row->Speed != 0 && $nextRow->Speed != 0 && $distance > $this->params['distance']) { //  && $distance > $this->params['distance']
                            if (count($places) - 1 == $placeIndex) {
                                $places = $this->endPlace($places, $placeIndex, $row);
                                $placeIndex++;
                            }
                        }
                    }
                } else {
                    if ($this->placeStatus == self::PLACE_STATUS_START) {
                        $places = $this->endPlace($places, $placeIndex, $row);
                    }
                }
            }

            if ($places) {
                $places = $this->filterPlaces($places);

                $places = $this->fixToRealMicrosoftLocations($places, $date, $dateNext, $microsoftUser);
                $count += count($places);

                $this->saveTimeTracker($places);
            }
        }
        return $count;
    }

    public function fixToRealMicrosoftLocations($places, $date, $dateNext, $microsoftUser)
    {
        // For testing functional
//        foreach ($places as $key => $place) {
//            if ($place['locationName'] === '2250 Monroe St #325, 2250 Monroe St #325, Santa Clara, CA, 95050') {
//                unset($places[$key]);
//            }
//            if ($place['locationName'] === '1235 Francisco Avenue, Unit A, San Jose, CA, 95126') {
//                unset($places[$key]);
//            }
//        }

        $allMicrosoftLocations = [];
        foreach ($places as $place) {
            if ($place['isMicrosoftLocation']) {
                $allMicrosoftLocations[$place['locationName']] = [
                    'location' => $place['locationName'],
                ];
            }
        }

        $this->log("GPS-Matched Microsoft Locations", array_keys($allMicrosoftLocations));

        $ext = [];
        $locations = MicrosoftLocation::find()
            ->where(['>=', 'date_time', $date])
            ->andWhere(['microsoft_id' => $microsoftUser->microsoft_id])
            ->andWhere(['<', 'date_time', $dateNext])
            ->orderBy('date_time', SORT_ASC)
            ->asArray()
            ->all();

        $this->log("Total Microsoft Calendar appointments", array_map(function($l) {
            return [
                'displayName' => $l['displayName'],
                'date_time' => $l['date_time'],
                'has_coords' => ($l['lat'] && $l['lon']) ? 'YES' : 'NO'
            ];
        }, $locations));

        foreach ($locations as $key => $location) {
            if (!isset($allMicrosoftLocations[$location['displayName']])) {
                $location['key'] = $key;
                $ext[] = $location;
                $this->log("- MISSING GPS for appointment: {$location['displayName']} at {$location['date_time']}");
            } else {
                $this->log("+ GPS MATCHED appointment: {$location['displayName']} at {$location['date_time']}");
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
                        $clock_out = date(
                            'h:i:s A',
                            strtotime('+10 minutes', strtotime($places[$key]['clock_out']))
                        );

                        $myLastElement = end($newPlaces);

                        if (isset($myLastElement['clock_in']) && $myLastElement['clock_in'] == $clock_out){
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
                                'start' => [
                                    ''
                                ],
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
                                'end' => [
                                    ''
                                ],
                            ];

                            $newPlaces[] = $tmp;
                            $added = true;

                            $newPlaces[] = $place;
                            $tmpCount = [];
                            foreach($newPlaces as $subPlace){
                                if($subPlace['isMicrosoftLocation']){
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

    private function startPlace($places, $placeIndex, $row, $user): array
    {
        $this->placeStatus = self::PLACE_STATUS_START;

        if (!isset($places[$placeIndex]['start'])) {
            $places[$placeIndex]['start'] = $row->getAttributes();
            $geoPlace = $this->checkGeoCodePlace($places[$placeIndex]['start'], $user);
            $places[$placeIndex]['isMicrosoftLocation'] = $geoPlace['isMicrosoftLocation'];
            $places[$placeIndex]['locationName'] = $geoPlace['locationName'];
            $places[$placeIndex]['locationNameVerizon'] = $geoPlace['locationNameVerizon'];
            $places[$placeIndex]['haul_away'] = $geoPlace['haul_away'];
            $places[$placeIndex]['user_id'] = $user->microsoft_id;
            $places[$placeIndex]['user'] = $user->name;
            $date = new \DateTime($places[$placeIndex]['start']['UpdateUtc']);
            $places[$placeIndex]['date'] = $date->format('Y-m-d H:i:s');
        }

        $date = new \DateTime($places[$placeIndex]['start']['UpdateUtc']);
        $places[$placeIndex]['clock_in'] = $date->format('h:i:s A');

        return $places;
    }

    private function endPlace($places, $placeIndex, $row): array
    {
        $this->placeStatus = "";
        $places[$placeIndex]['end'] = $row->getAttributes();
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

    private function checkGeoCodePlace($place, $microsoftUser)
    {
        $date = (new \DateTime($place['UpdateUtc']))->format('Y-m-d');
        $dateNext = (new \DateTime($place['UpdateUtc']))->modify('+1 day')->format('Y-m-d');
        $isMicrosoftLocation = false;
        $haul_away = false;
        
        // Build locationNameVerizon from available address data
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

        // Find CLOSEST Microsoft location within threshold
        $bestMatch = null;
        $bestDistance = PHP_FLOAT_MAX;
        $closestOverall = null;
        $closestOverallDistance = PHP_FLOAT_MAX;

        foreach ($locations as $location) {
            /** @var MicrosoftLocation $location */
            
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
                    $this->log("! Skipping location (no coords): {$location->displayName}");
                    continue;
                }
            }
            
            $distance = $this->getDistance(
                (float)$location->lat,
                (float)$location->lon,
                $place['Latitude'],
                $place['Longitude']
            );

            $this->log("Distance check", [
                'verizon_location' => $locationNameVerizon,
                'gps_coords' => $place['Latitude'] . ', ' . $place['Longitude'],
                'gps_time' => $place['UpdateUtc'],
                'microsoft_location' => $location->displayName,
                'microsoft_coords' => $location->lat . ', ' . $location->lon,
                'microsoft_time' => $location->date_time,
                'distance_meters' => round($distance, 2),
                'threshold_meters' => $this->params['distance'],
                'match' => $distance < $this->params['distance'] ? 'YES' : 'NO'
            ]);
            
            // Track closest overall (for logging)
            if ($distance < $closestOverallDistance) {
                $closestOverallDistance = $distance;
                $closestOverall = $location;
            }
            
            // Find closest match within threshold
            if ($distance < $this->params['distance'] && $distance < $bestDistance) {
                $bestDistance = $distance;
                $bestMatch = $location;
            }
        }
        
        // Use the CLOSEST match
        if ($bestMatch !== null) {
            $isMicrosoftLocation = true;
            $locationName = $bestMatch->displayName;
            $haul_away = $bestMatch->haul_away;
            
            $this->log("+ GPS MATCHED to: {$locationName} (distance: " . round($bestDistance, 2) . "m)");
        } else {
            if ($closestOverall) {
                $this->log("- NO MATCH - Closest was: {$closestOverall->displayName} (distance: " . round($closestOverallDistance, 2) . "m, threshold: {$this->params['distance']}m)");
            } else {
                $this->log("- NO MATCH - No Microsoft locations with coordinates found");
            }
        }

        return compact('isMicrosoftLocation', 'locationName', 'haul_away', 'locationNameVerizon');
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
                if (!isset($place['locationNameVerizon'])){
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
            if (((int)$explode[0] > 0) || ((int)$explode[1] - self::DEFAULT_TIME > 0)) {
                $result[] = $place;
            }
        }
        return $result;
    }

}