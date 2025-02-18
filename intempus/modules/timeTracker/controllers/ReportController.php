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
        $dataProvider->pagination->pageSize=50;

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
        $dataProvider->pagination->pageSize=50;


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
        if(!$dateStart && !$dateEnd){
            $dateStart = date('Y-m-01'). ' 00:00:00';
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
        $dataProvider->pagination->pageSize=1000;
        $data = $dataProvider->models;
        $calculatedData = [];
        $totalDay = [];
        foreach ($data as $key => $item) {
            if (!$item->isMicrosoftLocation){
                continue;
            }
            $itemCalc = $item->toArray();
            $itemCalc['rule1'] = $itemCalc['duration'];

            // rule 1
            if (empty($calculatedData) || !isset($data[$key - 1])){
                $itemCalc['rule1'] = DateTimeHelper::addMinutes($itemCalc['rule1'], 15);
            } else {
                $prev = $data[$key - 1]->clock_out;
                $roundedPrev = date('h:i', round(strtotime($prev)/60)*60);
                $cur = $data[$key]->clock_in;
                $roundedCur = date('h:i', round(strtotime($cur)/60)*60);

                $diff = DateTimeHelper::diff($roundedCur, $roundedPrev, true);
                if ($diff > 45){
                    $itemCalc['rule1'] = DateTimeHelper::addMinutes($itemCalc['rule1'], 30);
                } elseif ($diff <= 30){
                    $itemCalc['rule1'] = DateTimeHelper::addMinutes($itemCalc['rule1'], 15);
                }
            }

            // rule 2
            $itemCalc['rule2'] = $itemCalc['rule1'];
            if ($itemCalc['haul_away']){
                $itemCalc['rule2'] = DateTimeHelper::addMinutes($itemCalc['rule2'], 60);
            }

            // rule 3
            $itemCalc['rule3'] = DateTimeHelper::roundToHour($itemCalc['rule2']);

            // rule 4
            $itemCalc['rule4'] = DateTimeHelper::complexRounding($itemCalc['rule3']);

            $totalDay[$itemCalc['date']] = DateTimeHelper::addition($totalDay[$itemCalc['date']] ?? '00:00', $itemCalc['rule4']);
            $calculatedData[] = $itemCalc;
        }

        $dataProvider = new ArrayDataProvider([
            'allModels' => $calculatedData,
            'pagination' => [ 'pageSize' => 1000, ],
        ]);

        return $this->render('userBillable', [
            'provider' => $dataProvider,
            'filter' => $filterModel,
            'userName' => $userName,
            'timeTrackerItem' => $timeTrackerItem,
            'id' => $id,
            'totalDay' => $totalDay,
        ]);
    }

}
