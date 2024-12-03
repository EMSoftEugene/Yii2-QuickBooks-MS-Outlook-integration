<?php


namespace app\modules\timeTracker\services;

use app\modules\timeTracker\models\MicrosoftLocation;
use app\modules\timeTracker\models\TimeTracker;
use app\modules\timeTracker\models\TsheetGeolocation;
use app\modules\timeTracker\models\TsheetUser;
use app\modules\timeTracker\traits\CoordinateTrait;
use SebastianBergmann\CodeCoverage\Report\PHP;

class TimeTrackerService
{
    use CoordinateTrait;

    const DEFAULT_TIME = 1;
    private array $params;

    public function __construct()
    {
        $module = \Yii::$app->getModule('timeTracker');
        $this->params = $module->params;
    }

    public function create($date): int
    {
        $count = 0;
        $startDate = $date . ' 00:00:00';
        $endDate = $date . ' 23:59:59';
        $userIds = TsheetGeolocation::find()
            ->select('tsheet_user_id')
            ->where(['between', 'tsheet_created', $startDate, $endDate])
            ->groupBy('tsheet_user_id')
            ->column();

        foreach ($userIds as $userId) {
            echo $userId.PHP_EOL;

            $places = [];
            $placeIndex = 0;
            $tsheetUser = TsheetUser::findOne(['external_id' => $userId]);

            $rowsArr = TsheetGeolocation::find()
                ->where(['tsheet_user_id' => $userId])
                ->andWhere(['between', 'tsheet_created', $startDate, $endDate])
                ->orderBy('tsheet_created ASC')
                ->asArray()
                ->all();

            $rows = TsheetGeolocation::find()
                ->where(['tsheet_user_id' => $userId])
                ->andWhere(['between', 'tsheet_created', $startDate, $endDate])
                ->orderBy('tsheet_created ASC')
                ->all();
            echo "countRows=" . count($rows) . PHP_EOL;

            foreach ($rows as $key => $row) {
                echo 'key=' . $key . PHP_EOL;

                $nextRow = $rows[$key + 1] ?? null;
                if ($nextRow) {
                    $startLat = isset($places[$placeIndex]['start']) ? (float)$places[$placeIndex]['start']['lat'] : (float)$row->lat;
                    $startLon = isset($places[$placeIndex]['start']) ? (float)$places[$placeIndex]['start']['lon'] : (float)$row->lon;

                    $distance = $this->getDistance((float)$nextRow->lat, (float)$nextRow->lon, $startLat, $startLon);

                    if ($distance > $this->params['distance']) {
                        if (count($places) - 1 == $placeIndex) {
                            $places = $this->endPlace($places, $placeIndex, $row);

                            $placeIndex++;
                        }
                        continue;
                    }
                    $places = $this->startPlace($places, $placeIndex, $row, $tsheetUser);
                    continue;
                }

                if (isset($places[$placeIndex]['start']) && !isset($places[$placeIndex]['end'])) {
                    $places = $this->endPlace($places, $placeIndex, $row);
                }
            }

            if ($places) {
                $places = $this->filterPlaces($places);
                $count += count($places);
                $this->saveTimeTracker($places);
            }
        }
        return $count;
    }

    private function startPlace($places, $placeIndex, $row, TsheetUser $tsheetUser): array
    {
        if (!isset($places[$placeIndex]['start'])) {
            $places[$placeIndex]['start'] = $row->getAttributes();
            $geoPlace = $this->checkGeoCodePlace($places[$placeIndex]['start']);
            $places[$placeIndex]['isMicrosoftLocation'] = $geoPlace['isMicrosoftLocation'];
            $places[$placeIndex]['locationName'] = $geoPlace['locationName'];
            $places[$placeIndex]['user_id'] = $tsheetUser->id;
            $places[$placeIndex]['user'] = $tsheetUser->first_name . ' ' . $tsheetUser->last_name;
            $date = new \DateTime($places[$placeIndex]['start']['tsheet_created']);
            $places[$placeIndex]['date'] = $date->format('Y-m-d H:i:s');
        }

        $date = new \DateTime($places[$placeIndex]['start']['tsheet_created']);
        $places[$placeIndex]['clock_in'] = $date->format('h:i A');


        return $places;
    }

    private function endPlace($places, $placeIndex, $row): array
    {
        $places[$placeIndex]['end'] = $row->getAttributes();
        $startDate = (new \DateTime($places[$placeIndex]['start']['tsheet_created']))->format('Y-m-d H:i');
        $startDate = new \DateTime($startDate);
        $endDate = (new \DateTime($places[$placeIndex]['end']['tsheet_created']))->format('Y-m-d H:i');
        $endDate = new \DateTime($endDate);

        $diff = $startDate->diff($endDate);

        $h = str_pad($diff->h, 2, '0', STR_PAD_LEFT);
        $i = str_pad($diff->i, 2, '0', STR_PAD_LEFT);
        $duration = $h . ':' . $i;
        $places[$placeIndex]['duration'] = $duration;
        $places[$placeIndex]['clock_out'] = $endDate->format('h:i A');

        return $places;
    }

    private function checkGeoCodePlace($place)
    {
        $isMicrosoftLocation = false;
        $locationName = $this->getLocationByCode($place['lat'], $place['lon']);
        $locations = MicrosoftLocation::find()->all();
        foreach ($locations as $location) {
            $distance = $this->getDistance((float)$location->lat, (float)$location->lon, $place['lat'], $place['lon']);
            if ($distance < $this->params['distance']) {
                $isMicrosoftLocation = true;
                $locationName = $location->displayName;
                break;
            }
        }

        return compact('isMicrosoftLocation', 'locationName');
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
                $timeTracker->date = $place['date'];
                $timeTracker->clock_in = $clock_in;
                $timeTracker->clock_out = $clock_out;
                $timeTracker->duration = $place['duration'];
                $timeTracker->user_id = $place['user_id'];
                $timeTracker->user = $place['user'];
                $timeTracker->save();
            }
        }
    }

    private function filterPlaces($places): array
    {
        $result = [];
        foreach ($places as $place) {
            if($place['duration'] == '00:00'){
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