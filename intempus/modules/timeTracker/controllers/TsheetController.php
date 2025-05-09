<?php

namespace app\modules\timeTracker\controllers;

use app\modules\timeTracker\models\TsheetUserRaw;
use app\modules\timeTracker\models\TsheetUserRawSearch;
use app\modules\timeTracker\services\interfaces\ApiInterface;
use app\modules\timeTracker\services\TsheetDataService;
use app\modules\timeTracker\services\TsheetService;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;

class TsheetController extends BaseController
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
                'only' => ['index', 'refresh'],
                'rules' => [
                    [
                        'actions' => ['index', 'refresh'],
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
        $this->apiService = new TsheetService();
    }

    /**
     * Redirect to Tsheet Auth page
     *
     * @return Response
     */
    public function actionIndex()
    {
        return $this->redirect($this->apiService->getAuthUrl());
    }


    /**
     * Handle callback request
     *
     * @return Response
     */
    public function actionCallback()
    {
        try {
            $code = \Yii::$app->request->get('code');
            $result = $this->apiService->exchangeAuthCode($code);
            $this->apiService->updateUserAuth($result);
            \Yii::$app->session->setFlash('success', 'Successful authentication');
        } catch (\Exception $e) {
            \Yii::$app->session->setFlash('error', 'Error' . $e->getMessage() . ' | ' . $e->getTraceAsString());
            \Yii::info('auth error: ' . $e->getMessage() . ' | ' . $e->getTraceAsString());
        }

        return $this->redirect('/time-tracker');
    }

    /**
     * Refresh token
     *
     * @return Response
     */
    public function actionRefresh()
    {
        try {
            $this->apiService->refreshToken();
            \Yii::$app->session->setFlash('success', 'Successful refreshing');
        } catch (\Exception $e) {
            \Yii::$app->session->setFlash('error', 'Error refreshing' . $e->getMessage() . ' | ' . $e->getTraceAsString());
            \Yii::info('Error refreshing: ' . $e->getMessage() . ' | ' . $e->getTraceAsString());
        }

        return $this->redirect('/time-tracker');
    }

    /**
     * Refresh token
     *
     * @return Response
     */
    public function actionUsers()
    {
        $apiDataService = new TsheetDataService();
        $users = $apiDataService->getAllUsers();

        return $this->render('usersAll', [
            'data' => $users,
        ]);
    }

    /**
     * Refresh token
     *
     * @return Response
     */
    public function actionUsersRaw()
    {
        $filterModel = new TsheetUserRawSearch();
        $dataProvider = $filterModel->search(\Yii::$app->request->get());

        return $this->render('usersRaw', [
            'provider' => $dataProvider,
            'filter' => $filterModel,
        ]);
    }

}
