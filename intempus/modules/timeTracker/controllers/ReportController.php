<?php

namespace app\modules\timeTracker\controllers;

use app\modules\timeTracker\models\TimeTracker;
use app\modules\timeTracker\models\TimeTrackerSearch;
use app\modules\timeTracker\models\TsheetUser;
use app\modules\timeTracker\services\TsheetDataService;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;

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

}
