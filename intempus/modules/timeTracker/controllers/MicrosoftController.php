<?php

namespace app\modules\timeTracker\controllers;

use app\modules\timeTracker\models\MicrosoftGroupSearch;
use app\modules\timeTracker\services\interfaces\ApiInterface;
use app\modules\timeTracker\services\MicrosoftService;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;

class MicrosoftController extends BaseController
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
        $this->apiService = new MicrosoftService();
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
            \Yii::$app->session->setFlash('success','Successful authentication');
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
    public function actionGroups()
    {
        $filterModel = new MicrosoftGroupSearch();
        $dataProvider = $filterModel->search(\Yii::$app->request->get());

        return $this->render('groups', [
            'provider' => $dataProvider,
            'filter' => $filterModel,
        ]);
    }
}
