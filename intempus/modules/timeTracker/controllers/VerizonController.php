<?php

namespace app\modules\timeTracker\controllers;

use app\modules\timeTracker\models\VehiclesHistorySearch;
use app\modules\timeTracker\models\MicrosoftGroupSearch;
use app\modules\timeTracker\services\interfaces\ApiInterface;
use app\modules\timeTracker\services\MicrosoftService;
use app\modules\timeTracker\services\VerizonService;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;

class VerizonController extends BaseController
{
    private ApiInterface $apiService;

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'only' => ['history'],
                'rules' => [
                    [
                        'actions' => ['history'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    public function init()
    {
        parent::init();
        $this->apiService = new VerizonService();
    }

    /**
     * Refresh token
     *
     * @return Response
     */
    public function actionHistory()
    {
        $filterModel = new VehiclesHistorySearch();
        $dataProvider = $filterModel->search(\Yii::$app->request->get());

        return $this->render('history', [
            'provider' => $dataProvider,
            'filter' => $filterModel,
        ]);
    }
}
