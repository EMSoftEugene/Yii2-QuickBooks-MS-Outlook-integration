<?php

namespace app\modules\timeTracker\controllers;

use app\modules\timeTracker\models\TimeTracker;
use app\modules\timeTracker\models\TimeTrackerSearch;
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
        $dataProvider = $filterModel->search($getParams);
        $dataProvider->pagination->pageSize=50;

        return $this->render('userItem', [
            'provider' => $dataProvider,
            'filter' => $filterModel,
            'userName' => $userName,
            'timeTrackerItem' => $timeTrackerItem
        ]);
    }
}
