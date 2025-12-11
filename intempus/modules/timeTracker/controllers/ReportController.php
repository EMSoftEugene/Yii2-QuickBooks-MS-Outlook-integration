<?php

namespace app\modules\timeTracker\controllers;

use app\modules\timeTracker\helper\DateTimeHelper;
use app\modules\timeTracker\models\MicrosoftLocation;
use app\modules\timeTracker\models\TimeTracker;
use app\modules\timeTracker\models\TimeTrackerSearch;
use app\modules\timeTracker\models\TsheetGeolocation;
use app\modules\timeTracker\models\TsheetGeolocationSearch;
use app\modules\timeTracker\models\TsheetUser;
use app\modules\timeTracker\services\TsheetDataService;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;
use yii\data\ArrayDataProvider;

class ReportController extends BaseController
{

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'only' => ['location'],
                'rules' => [
                    [
                        'actions' => ['location'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    /**
     *
     * @return string
     */
    public function actionLocation()
    {
        $filterModel = new TimeTrackerSearch();
        $dataProvider = $filterModel->searchIndex(\Yii::$app->request->get());
        $dataProvider->setPagination(['pageSize' => 20]);

        return $this->render('location', [
            'provider' => $dataProvider,
            'filter' => $filterModel,
        ]);
    }

    /**
     *
     * @return string
     */
    public function actionLocationItem($id)
    {
        $timeTrackerItem = TimeTracker::findOne($id);
        $locationName = $timeTrackerItem->locationName;
        $filterModel = new TimeTrackerSearch();
        $getParams = array_merge(\Yii::$app->request->get(), ['locationName' => $locationName]);

        if (\Yii::$app->request->isAjax) {
            return $filterModel->autocompleteUser($getParams);
        }

        $dataProvider = $filterModel->search($getParams);
        $dataProvider->pagination->pageSize = 50;

        return $this->render('locationItem', [
            'provider' => $dataProvider,
            'filter' => $filterModel,
            'locationName' => $locationName,
            'timeTrackerItem' => $timeTrackerItem
        ]);
    }

    /**
     *
     * @return string
     */
    public function actionUser()
    {
        $filterModel = new TimeTrackerSearch();
        $dataProvider = $filterModel->searchUsers(\Yii::$app->request->get());
        $dataProvider->setPagination(['pageSize' => 20]);

        return $this->render('user', [
            'provider' => $dataProvider,
            'filter' => $filterModel,
        ]);
    }

    /**
     *
     * @return string
     */
    public function actionUserItem($id)
    {
        $timeTrackerItem = TimeTracker::findOne($id);
        $userName = $timeTrackerItem->user;
        $filterModel = new TimeTrackerSearch();
        $getParams = array_merge(\Yii::$app->request->get(), ['userName' => $userName]);

        if (\Yii::$app->request->isAjax) {
            return $filterModel->autocompleteLocation($getParams);
        }

        $dataProvider = $filterModel->search($getParams);
        $dataProvider->pagination->pageSize = 50;


        return $this->render('userItem', [
            'provider' => $dataProvider,
            'filter' => $filterModel,
            'userName' => $userName,
            'timeTrackerItem' => $timeTrackerItem,
            'id' => $id,
        ]);
    }

    /**
     *
     * @return string
     */
    public function actionUserRaw($id)
    {
        $timeTrackerItem = TimeTracker::findOne($id);
        $userName = $timeTrackerItem->user;
        $userId = $timeTrackerItem->user_id;
        $tsheetUser = TsheetUser::findOne(['id' => $userId]);
        $tsheetUserId = $tsheetUser->external_id ?? 0;

        $dateStart = \Yii::$app->request->get('date_start');
        $dateEnd = \Yii::$app->request->get('date_end');
        if (!$dateStart && !$dateEnd) {
            $dateStart = date('Y-m-01') . ' 00:00:00';
            $dateEnd = date('Y-m-t') . ' 23:59:59';
        }

        $tsheetDataService = new TsheetDataService();
        $data = $tsheetDataService->getUserGeolocations($tsheetUserId, $dateStart, $dateEnd);
        $data = json_encode($data, JSON_PRETTY_PRINT);

        return $this->render('userRaw', [
            'data' => $data,
            'userName' => $userName,
            'id' => $id,
        ]);
    }

    /**
     *
     * @return string
     */
    public function actionUserGeolocations($id)
    {
        $timeTrackerItem = TimeTracker::findOne($id);
        $userName = $timeTrackerItem->user;
        $userId = $timeTrackerItem->user_id;
        $tsheetUser = TsheetUser::findOne(['id' => $userId]);
        $tsheetUserId = $tsheetUser->external_id ?? 0;

        $getParams = array_merge(\Yii::$app->request->get(), ['tsheetUserId' => $tsheetUserId]);

        $filterModel = new TsheetGeolocationSearch();
        $dataProvider = $filterModel->search($getParams);

        return $this->render('userGeolocations', [
            'provider' => $dataProvider,
            'filter' => $filterModel,
            'userName' => $userName,
            'id' => $id,
        ]);
    }

    /**
     * @param array $data
     * @return array
     */
    private function mergeConsecutiveMicrosoftLocations($data)
    {
        $mergedData = [];
        $currentGroup = null;
        $groupCount = 0;

        foreach ($data as $index => $item) {

            if ($item->isMicrosoftLocation) {
                if ($currentGroup === null) {
                    $currentGroup = [
                        'items' => [$item],
                        'locationName' => $item->locationName,
                        'clock_in' => $item->clock_in,
                        'clock_out' => $item->clock_out,
                        'date' => $item->date,
                        'isMicrosoftLocation' => true,
                    ];
                } elseif ($currentGroup['locationName'] === $item->locationName) {
                    $currentGroup['items'][] = $item;
                    $currentGroup['clock_out'] = $item->clock_out;
                    $groupCount++;
                } else {
                    $mergedItem = $this->createMergedItem($currentGroup);
                    $mergedData[] = $mergedItem;

                    $currentGroup = [
                        'items' => [$item],
                        'locationName' => $item->locationName,
                        'clock_in' => $item->clock_in,
                        'clock_out' => $item->clock_out,
                        'date' => $item->date,
                        'isMicrosoftLocation' => true,
                    ];
                }
            } else {
                if ($currentGroup === null) {
                    $mergedData[] = $item;
                } else {
                    continue;
                }
            }
        }

        if ($currentGroup !== null) {
            $mergedItem = $this->createMergedItem($currentGroup);
            $mergedData[] = $mergedItem;
        }

        usort($mergedData, function($a, $b) {
            $dateCompare = strcmp($a->date, $b->date);
            if ($dateCompare !== 0) {
                return $dateCompare;
            }
            return strcmp($a->clock_in, $b->clock_in);
        });

        return $mergedData;
    }

    /**
     * @param array $group
     * @return TimeTracker
     */
    private function createMergedItem($group)
    {
        $firstItem = $group['items'][0];
        $mergedItem = clone $firstItem;

        $mergedItem->clock_in = $group['clock_in'];
        $mergedItem->clock_out = $group['clock_out'];

        $duration = DateTimeHelper::diff($group['clock_out'], $group['clock_in']);
        $mergedItem->duration = $duration;

        return $mergedItem;
    }

    /**
     *
     * @return string
     */
    public function actionUserBillable($id): string
    {
        $timeTrackerItem = TimeTracker::findOne($id);
        $userName = $timeTrackerItem->user;
        $filterModel = new TimeTrackerSearch();
        $getParams = array_merge(\Yii::$app->request->get(), ['userName' => $userName]);
        $dataProvider = $filterModel->search($getParams);
        $dataProvider->pagination->pageSize = 1000;
        $data = $dataProvider->models;

        $data = $this->mergeConsecutiveMicrosoftLocations($data);

        $calculatedData = [];
        $totalDay = [];
        $formula = [];
        $i = 0;
        $prevLocation = null;
        foreach ($data as $key => $item) {
            if (!$item->isMicrosoftLocation) {
                continue;
            }
            $i++;
            $itemCalc = $item->toArray();
            $itemCalc['rule1'] = $itemCalc['duration'];
            $itemCalc['rule1_desc'] = '';
            $itemCalc['rulex0_desc'] = '';
            $itemCalc['rulex1_desc'] = '';
            if (!isset($formula[$itemCalc['date']])) {
                $i = 1;
                $formula[$itemCalc['date']] = [0 => null, 1 => null, 2 => null];
            }
            $itemCalc['L'] = $i;

            // rule 1
            $cur = $data[$key]->clock_in;
            $roundedCur = date('H:i', floor(strtotime($cur) / 60) * 60);
            $itemCalc['rule1_desc'] = '<b>Description</b>: Add drive time from previous Microsoft appointment only.';
            $itemCalc['rule1_desc'] .= '<br/><b>Formula</b>:';
            $itemCalc['rule1_desc'] .= '<br/>- First Microsoft appointment of the day -> AddValue = 0';
            $itemCalc['rule1_desc'] .= '<br/>- Next Microsoft appointment -> AddValue = diff(prev_out, current_in)';
            $itemCalc['rule1_desc'] .= '<br/>- Only if diff ≥ 5 minutes';
            $itemCalc['rule1_desc'] .= '<br/>- Use ONLY previous Microsoft location';
            $itemCalc['rule1_desc'] .= '<br/><b>Calculate</b>:';

            $addValue = 0;
            $curClockIn = $item->clock_in;
            $curDate = date('Y-m-d', strtotime($item->date));
            $roundedCur = date('H:i', round(strtotime($curClockIn) / 60) * 60);

            $prev = null;
            for ($j = $key - 1; $j >= 0; $j--) {
                if ($data[$j]->isMicrosoftLocation) {
                    $prev = $data[$j];
                    break;
                }
            }

            if (!$prev) {
                $itemCalc['rule1_desc'] .= '<br/><b>First Microsoft of day -> AddValue = 0</b>';
            } else {

                $prevDate = date('Y-m-d', strtotime($prev->date));

                if ($curDate !== $prevDate) {
                    $itemCalc['rule1_desc'] .= '<br/><b>New day ->AddValue = 0</b>';
                } else {

                    $roundedPrev = date('H:i', round(strtotime($prev->clock_out) / 60) * 60);
                    $diff = DateTimeHelper::diff($roundedCur, $roundedPrev, true);

                    if ($diff >= 5 && $itemCalc['locationName'] !== $prev->locationName) {
                        $addValue = $diff;
                    }

                    $itemCalc['rule1_desc'] .= "<br/>Prev={$roundedPrev} ({$prev->locationName})";
                    $itemCalc['rule1_desc'] .= "<br/>Cur={$roundedCur} ({$itemCalc['locationName']})";
                    $itemCalc['rule1_desc'] .= "<br/>Diff={$diff}";
                    $itemCalc['rule1_desc'] .= "<br/>AddValue={$addValue}";
                }
            }

            $itemCalc['rule1'] = DateTimeHelper::addMinutes($itemCalc['rule1'], $addValue);
            $itemCalc['rule1_addValue'] = $addValue;

            $prevLocation = $itemCalc['locationName'];

            $itemCalc['rulex1_desc'] .= '<b>L' . $i . '(</b>';
            $itemCalc['rulex1_desc'] .=  DateTimeHelper::formatHM($itemCalc['duration']) . '#stop';
            if ($itemCalc['rule1'] != $itemCalc['duration']) {
                $dd = DateTimeHelper::diff($itemCalc['rule1'], $itemCalc['duration']);
                $itemCalc['rulex1_desc'] .= ' + ' . DateTimeHelper::formatHM($dd) . 'L' . $i . '#drive';
            }

            // rule 2
            $haulAway = 0;
            $itemCalc['rule2'] = $itemCalc['rule1'];
            $itemCalc['rule2_desc'] = '<b>Description</b>: Need to add 1 hour for appliance haul away and any trash removal. If we are replacing anything we will need to charge an extra hour. Say if we replace a door or blinds, we have to pay to dump it.';
            $itemCalc['rule2_desc'] .= '<br/><b>Formula</b>:';
            $itemCalc['rule2_desc'] .= '<br/>1. Rule2 = Rule1 HaulAway.';
            $itemCalc['rule2_desc'] .= '<br/>2. HaulAway = (if isset HaulAway in description) = 60 minutes.';
            $itemCalc['rule2_desc'] .= '<br/><b>Calculate</b>:';
            if ($itemCalc['haul_away']) {
                $haulAway = 60;
                $itemCalc['rule2'] = DateTimeHelper::addMinutes($itemCalc['rule2'], 60);
            }
            $itemCalc['rule2_desc'] .= '<br/>HaulAway= ' . (int)$itemCalc['haul_away'];
            $itemCalc['rule2_desc'] .= '<br/>Rule2 = ' . $itemCalc['rule1'] . ' + ' . $haulAway;
            $itemCalc['rule2_desc'] .= '<br/><b>Rule2 = ' . $itemCalc['rule2'] . '</b>';

            if ($itemCalc['rule2'] != $itemCalc['rule1']) {
                $dd = DateTimeHelper::diff($itemCalc['rule2'], $itemCalc['rule1']);
                $itemCalc['rulex1_desc'] .= ' + ' . DateTimeHelper::formatHM($dd) . '#houl_away';
            }

            // rule 3
            $itemCalc['rule3_desc'] = '<b>Description</b>: We bill a minimum of 1 hour.';
            $itemCalc['rule3_desc'] .= '<br/><b>Formula</b>:';
            $itemCalc['rule3_desc'] .= '<br/>1. Rule3 = roundToHour(Rule2)';
            $itemCalc['rule3_desc'] .= '<br/><b>Calculate</b>:';
            $itemCalc['rule3'] = DateTimeHelper::roundToHour($itemCalc['rule2']);
            $itemCalc['rule3_desc'] .= '<br/>Rule3 = roundToHour(' . $itemCalc['rule2'] . ')';
            $itemCalc['rule3_desc'] .= '<br/><b>Rule3 = ' . $itemCalc['rule3'] . '</b>';

            if ($itemCalc['rule3'] != $itemCalc['rule2']) {
                $dd = DateTimeHelper::diff($itemCalc['rule3'], $itemCalc['rule2']);
                $itemCalc['rulex1_desc'] .= ' + ' . DateTimeHelper::formatHM($dd) . '#minim_stop_1h';
            }

            // rule 4
            $itemCalc['rule4_desc'] = '<b>Description</b>: Round up when billing, Example: If a job takes 1 hour and five minutes we will bill one hour, but if it takes 1 hour and 6 or more minutes we will bill 1.5 hours. If a job takes 1 hour and 35 minutes we will bill 1.5 hours. If the job takes 1 hours and 36 or more minutes we will bill 2 hours.';
            $itemCalc['rule4_desc'] .= '<br/><b>Formula</b>:';
            $itemCalc['rule4_desc'] .= '<br/>1. Rule4 = roundUp(Rule3)';
            $itemCalc['rule4_desc'] .= '<br/><b>Calculate</b>:';
            $itemCalc['rule4'] = DateTimeHelper::complexRounding($itemCalc['rule3']);
            $itemCalc['rule4_desc'] .= '<br/>Rule4 = roundUp(' . $itemCalc['rule3'] . ')';
            $itemCalc['rule4_desc'] .= '<br/><b>Rule4 = ' . $itemCalc['rule4'] . '</b>';

            if ($itemCalc['rule4'] != $itemCalc['rule3']) {
                $dd = DateTimeHelper::diff($itemCalc['rule4'], $itemCalc['rule3']);
                $itemCalc['rulex1_desc'] .= ' + ' . DateTimeHelper::formatHM($dd) . '#rounding';
            }
            $itemCalc['rulex1_desc'] .= '<b>)</b> ';

            // clear value if not found
            if (!isset($item->locationNameVerizon) || !$item->locationNameVerizon) {
            } else {
                $totalDay[$itemCalc['date']] = DateTimeHelper::addition(
                    $totalDay[$itemCalc['date']] ?? '00:00',
                    $itemCalc['rule4']
                );

                $itemCalc['rulex0_desc'] .= DateTimeHelper::formatHM($itemCalc['rule4']) . 'L' . $i;
                $formula[$itemCalc['date']][0] .= empty($formula[$itemCalc['date']][0]) ?
                    $itemCalc['rulex0_desc'] : ' + ' . $itemCalc['rulex0_desc'];
                $formula[$itemCalc['date']][1] .= empty($formula[$itemCalc['date']][1]) ?
                    $itemCalc['rulex1_desc'] : ' + ' . $itemCalc['rulex1_desc'];
                $formula[$itemCalc['date']][2] .= empty($formula[$itemCalc['date']][2]) ?
                    $itemCalc['rulex1_desc'] : ' + ' . $itemCalc['rulex1_desc'];
            }

            $calculatedData[] = $itemCalc;
        }

        $totalDay0 = $totalDay;
        foreach ($totalDay as $key => &$item) {
            $extraMinutes = DateTimeHelper::diff($item, '08:00', true);
            if ($extraMinutes < 0) {
                continue;
            }

            $extraHours = $extraMinutes / 60;
            $extraValue = $extraHours * 0.5;

            if ($extraValue > 0) {
                $extraMinutesValue = $extraValue * 60;

                $hours = floor($extraMinutesValue / 60);
                $minutes = $extraMinutesValue % 60;
                $timeString = sprintf("%02d:%02d", $hours, $minutes);

                $extraValueDesc = DateTimeHelper::formatHM($timeString);
                $extraValueFormatted = $timeString;

                $item = DateTimeHelper::addition(
                    $item ?? '00:00',
                    $extraValueFormatted
                );
                $formula[$key][1] .= ' + ' . $extraValueDesc . '#overtime';
            }
        }

        $dataProvider = new ArrayDataProvider([
            'allModels' => $calculatedData,
            'pagination' => ['pageSize' => 1000,],
        ]);

        return $this->render('userBillable', [
            'provider' => $dataProvider,
            'filter' => $filterModel,
            'userName' => $userName,
            'timeTrackerItem' => $timeTrackerItem,
            'id' => $id,
            'totalDay' => $totalDay,
            'totalDay0' => $totalDay0,
            'formula' => $formula,
        ]);
    }

    /**
     *
     * @return string
     */
    public function actionUserBillableConsolidated($id): string
    {
        $timeTrackerItem = TimeTracker::findOne($id);
        $userName = $timeTrackerItem->user;
        $filterModel = new TimeTrackerSearch();
        $getParams = array_merge(\Yii::$app->request->get(), ['userName' => $userName]);
        $dataProvider = $filterModel->search($getParams);
        $dataProvider->pagination->pageSize = 1000;
        $data = $dataProvider->models;

        $data = $this->mergeConsecutiveMicrosoftLocations($data);

        $calculatedData = [];
        $totalDay = [];
        $formula = [];
        $i = 0;
        $prevLocation = null;
        foreach ($data as $key => $item) {
            if (!$item->isMicrosoftLocation) {
                continue;
            }
            $i++;
            $itemCalc = $item->toArray();
            $itemCalc['rule1'] = $itemCalc['duration'];
            $itemCalc['rule1_desc'] = '';
            $itemCalc['rulex0_desc'] = '';
            $itemCalc['rulex1_desc'] = '';
            if (!isset($formula[$itemCalc['date']])) {
                $i = 1;
                $formula[$itemCalc['date']] = [0 => null, 1 => null, 2 => null];
            }
            $itemCalc['L'] = $i;

            // rule 1
            $cur = $data[$key]->clock_in;
            $roundedCur = date('h:i', round(strtotime($cur) / 60) * 60);
            $itemCalc['rule1_desc'] = '<b>Description</b>: Add drive time from previous Microsoft appointment only.';
            $itemCalc['rule1_desc'] .= '<br/><b>Formula</b>:';
            $itemCalc['rule1_desc'] .= '<br/>- First Microsoft appointment of the day -> AddValue = 0';
            $itemCalc['rule1_desc'] .= '<br/>- Next Microsoft appointment -> AddValue = diff(prev_out, current_in)';
            $itemCalc['rule1_desc'] .= '<br/>- Only if diff ≥ 5 minutes AND locations differ';
            $itemCalc['rule1_desc'] .= '<br/>- Never add between different days';
            $itemCalc['rule1_desc'] .= '<br/>- Never use non-Microsoft previous rows';

            $itemCalc['rule1'] = $itemCalc['duration'];
            $itemCalc['rule1_addValue'] = 0;

            $curClockIn = $item->clock_in;
            $curDate = date('Y-m-d', strtotime($item->date));
            $roundedCur = date('H:i', round(strtotime($curClockIn) / 60) * 60);

            $prev = null;
            for ($j = $key - 1; $j >= 0; $j--) {
                if ($data[$j]->isMicrosoftLocation) {
                    $prev = $data[$j];
                    break;
                }
            }

            if (!$prev) {
                $itemCalc['rule1_desc'] .= '<br/><b>First Microsoft entry -> AddValue = 0</b>';
            } else {

                $prevDate = date('Y-m-d', strtotime($prev->date));

                if ($curDate !== $prevDate) {
                    $itemCalc['rule1_desc'] .= '<br/><b>New day detected -> AddValue = 0</b>';
                } else {

                    $prevClockOut = $prev->clock_out;
                    $roundedPrev = date('H:i', round(strtotime($prevClockOut) / 60) * 60);

                    $diff = DateTimeHelper::diff($roundedCur, $roundedPrev, true);
                    $addValue = 0;

                    if ($diff >= 5 && $itemCalc['locationName'] !== $prev->locationName) {
                        $addValue = $diff;
                    }

                    $itemCalc['rule1'] = DateTimeHelper::addMinutes($itemCalc['duration'], $addValue);
                    $itemCalc['rule1_addValue'] = $addValue;

                    $itemCalc['rule1_desc'] .= "<br/>Prev={$roundedPrev} ({$prev->locationName})";
                    $itemCalc['rule1_desc'] .= "<br/>Cur={$roundedCur} ({$itemCalc['locationName']})";
                    $itemCalc['rule1_desc'] .= "<br/>Diff={$diff}";
                    $itemCalc['rule1_desc'] .= "<br/><b>AddValue={$addValue}</b>";
                    $itemCalc['rule1_desc'] .= "<br/><b>Rule1={$itemCalc['rule1']}</b>";
                }
            }

            $prevLocation = $itemCalc['locationName'];

            $itemCalc['rulex1_desc'] .= '<b>L' . $i . '(</b>';
            $itemCalc['rulex1_desc'] .=  DateTimeHelper::formatHM($itemCalc['duration']) . '#stop';
            if ($itemCalc['rule1'] != $itemCalc['duration']) {
                $dd = DateTimeHelper::diff($itemCalc['rule1'], $itemCalc['duration']);
                $itemCalc['rulex1_desc'] .= ' + ' . DateTimeHelper::formatHM($dd) . 'L' . $i . '#extra';
            }

            // rule 2
            $haulAway = 0;
            $itemCalc['rule2'] = $itemCalc['rule1'];
            $itemCalc['rule2_desc'] = '<b>Description</b>: Need to add 1 hour for appliance haul away and any trash removal. If we are replacing anything we will need to charge an extra hour. Say if we replace a door or blinds, we have to pay to dump it.';
            $itemCalc['rule2_desc'] .= '<br/><b>Formula</b>:';
            $itemCalc['rule2_desc'] .= '<br/>1. Rule2 = Rule1 HaulAway.';
            $itemCalc['rule2_desc'] .= '<br/>2. HaulAway = (if isset HaulAway in description) = 60 minutes.';
            $itemCalc['rule2_desc'] .= '<br/><b>Calculate</b>:';
            if ($itemCalc['haul_away']) {
                $haulAway = 60;
                $itemCalc['rule2'] = DateTimeHelper::addMinutes($itemCalc['rule2'], 60);
            }
            $itemCalc['rule2_desc'] .= '<br/>HaulAway= ' . (int)$itemCalc['haul_away'];
            $itemCalc['rule2_desc'] .= '<br/>Rule2 = ' . $itemCalc['rule1'] . ' + ' . $haulAway;
            $itemCalc['rule2_desc'] .= '<br/><b>Rule2 = ' . $itemCalc['rule2'] . '</b>';

            if ($itemCalc['rule2'] != $itemCalc['rule1']) {
                $dd = DateTimeHelper::diff($itemCalc['rule2'], $itemCalc['rule1']);
                $itemCalc['rulex1_desc'] .= ' + ' . DateTimeHelper::formatHM($dd) . '#houl_away';
            }

            // rule 3
            $itemCalc['rule3_desc'] = '<b>Description</b>: We bill a minimum of 1 hour.';
            $itemCalc['rule3_desc'] .= '<br/><b>Formula</b>:';
            $itemCalc['rule3_desc'] .= '<br/>1. Rule3 = roundToHour(Rule2)';
            $itemCalc['rule3_desc'] .= '<br/><b>Calculate</b>:';
            $itemCalc['rule3'] = DateTimeHelper::roundToHour($itemCalc['rule2']);
            $itemCalc['rule3_desc'] .= '<br/>Rule3 = roundToHour(' . $itemCalc['rule2'] . ')';
            $itemCalc['rule3_desc'] .= '<br/><b>Rule3 = ' . $itemCalc['rule3'] . '</b>';

            if ($itemCalc['rule3'] != $itemCalc['rule2']) {
                $dd = DateTimeHelper::diff($itemCalc['rule3'], $itemCalc['rule2']);
                $itemCalc['rulex1_desc'] .= ' + ' . DateTimeHelper::formatHM($dd) . '#minim_stop_1h';
            }

            // rule 4
            $itemCalc['rule4_desc'] = '<b>Description</b>: Round up when billing, Example: If a job takes 1 hour and five minutes we will bill one hour, but if it takes 1 hour and 6 or more minutes we will bill 1.5 hours. If a job takes 1 hour and 35 minutes we will bill 1.5 hours. If the job takes 1 hours and 36 or more minutes we will bill 2 hours.';
            $itemCalc['rule4_desc'] .= '<br/><b>Formula</b>:';
            $itemCalc['rule4_desc'] .= '<br/>1. Rule4 = roundUp(Rule3)';
            $itemCalc['rule4_desc'] .= '<br/><b>Calculate</b>:';
            $itemCalc['rule4'] = DateTimeHelper::complexRounding($itemCalc['rule3']);
            $itemCalc['rule4_desc'] .= '<br/>Rule4 = roundUp(' . $itemCalc['rule3'] . ')';
            $itemCalc['rule4_desc'] .= '<br/><b>Rule4 = ' . $itemCalc['rule4'] . '</b>';

            if ($itemCalc['rule4'] != $itemCalc['rule3']) {
                $dd = DateTimeHelper::diff($itemCalc['rule4'], $itemCalc['rule3']);
                $itemCalc['rulex1_desc'] .= ' + ' . DateTimeHelper::formatHM($dd) . '#rounding';
            }
            $itemCalc['rulex1_desc'] .= '<b>)</b> ';

            // clear value if not found
            if (!isset($item->locationNameVerizon) || !$item->locationNameVerizon) {
                $totalDay[$itemCalc['date']] = DateTimeHelper::addition(
                    $totalDay[$itemCalc['date']] ?? '00:00',
                    '00:00'
                );
            } else {
                $totalDay[$itemCalc['date']] = DateTimeHelper::addition(
                    $totalDay[$itemCalc['date']] ?? '00:00',
                    $itemCalc['rule4']
                );

                $itemCalc['rulex0_desc'] .= DateTimeHelper::formatHM($itemCalc['rule4']) . 'L' . $i;
                $formula[$itemCalc['date']][0] .= empty($formula[$itemCalc['date']][0]) ?
                    $itemCalc['rulex0_desc'] : ' + ' . $itemCalc['rulex0_desc'];
                $formula[$itemCalc['date']][1] .= empty($formula[$itemCalc['date']][1]) ?
                    $itemCalc['rulex1_desc'] : ' + ' . $itemCalc['rulex1_desc'];
                $formula[$itemCalc['date']][2] .= empty($formula[$itemCalc['date']][2]) ?
                    $itemCalc['rulex1_desc'] : ' + ' . $itemCalc['rulex1_desc'];
            }

            $calculatedData[] = $itemCalc;
        }

        $totalDay0 = $totalDay;
        foreach ($totalDay as $key => &$item) {
            $extraMinutes = DateTimeHelper::diff($item, '08:00', true);
            if ($extraMinutes < 0) {
                continue;
            }

            $extraHours = $extraMinutes / 60;
            $extraValue = $extraHours * 0.5;

            if ($extraValue > 0) {
                $extraMinutesValue = $extraValue * 60;

                $hours = floor($extraMinutesValue / 60);
                $minutes = $extraMinutesValue % 60;
                $timeString = sprintf("%02d:%02d", $hours, $minutes);

                $extraValueDesc = DateTimeHelper::formatHM($timeString);
                $extraValueFormatted = $timeString;

                $item = DateTimeHelper::addition(
                    $item ?? '00:00',
                    $extraValueFormatted
                );
                $formula[$key][1] .= ' + ' . $extraValueDesc . '#overtime';
            }
        }

        foreach ($totalDay as $key => $item) {
            $resTotalDay[$key] = (string)$item;
        }

        $arr = [];
        foreach ($calculatedData as $key => $item) {
            $item['count'] = 1;

            $uniqueKey = $item['locationName'] . '|' . $item['date'];

            if (isset($arr[$uniqueKey])) {
                $arr[$uniqueKey]['rule4'] = DateTimeHelper::addition(
                    $arr[$uniqueKey]['rule4'],
                    $item['rule4']
                );
                $arr[$uniqueKey]['count']++;
            } else {
                $arr[$uniqueKey] = $item;
            }
        }
        $calculatedData = array_values($arr);

        $dataProvider = new ArrayDataProvider([
            'allModels' => $calculatedData,
            'pagination' => ['pageSize' => 1000,],
        ]);

        return $this->render('userBillableConsolidated', [
            'provider' => $dataProvider,
            'filter' => $filterModel,
            'userName' => $userName,
            'timeTrackerItem' => $timeTrackerItem,
            'id' => $id,
            'totalDay' => $totalDay,
            'totalDay0' => $totalDay0,
            'resTotalDay' => $resTotalDay,
            'formula' => $formula,
        ]);
    }
}