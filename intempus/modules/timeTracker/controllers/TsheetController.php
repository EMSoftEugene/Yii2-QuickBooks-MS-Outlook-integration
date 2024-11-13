<?php

namespace app\modules\timeTracker\controllers;

use app\modules\timeTracker\services\interfaces\ApiInterface;
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

    public function actionTimeEntries()
    {
        $date = \Yii::$app->request->post('date');
        try {
            $queryParams = [
                'start_date' => $date ?: date('Y-m-d'),
            ];
            $result = $this->apiService->requestGet('timesheets', $queryParams);
            $imported = $this->apiService->handleTimeSheet($result);
            \Yii::$app->session->setFlash('success', 'Success. Imported time sheets: ' . count($imported));
        } catch (\Exception $e) {
            $err = $e->getMessage() . ' | ' . $e->getLine() . ' | ' . $e->getFile();
            \Yii::$app->session->setFlash('error', $err);
            \Yii::info('error: ' . $e->getMessage() . ' | ' . $e->getTraceAsString());
        }
        $this->redirect('/time-tracker');
    }
}
