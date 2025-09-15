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
        $locationName = $place['location'];
        $locationNameVerizon = $place['location'];

        $locations = MicrosoftLocation::find()
            ->where(['>=', 'date_time', $date])
            ->andWhere(['<', 'date_time', $dateNext])
            ->andWhere(['microsoft_id' => $microsoftUser->microsoft_id])
            ->all();

        foreach ($locations as $location) {
            /** @var MicrosoftLocation $location */
            $distance = $this->getDistance(
                (float)$location->lat,
                (float)$location->lon,
                $place['Latitude'],
                $place['Longitude']
            );
            if ($distance < $this->params['distance']) {
                $isMicrosoftLocation = true;
                $locationName = $location->displayName;
                $haul_away = $location->haul_away;
                break;
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