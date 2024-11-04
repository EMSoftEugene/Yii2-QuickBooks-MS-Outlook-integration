<?php

namespace app\controllers;

use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;

class ReportController extends Controller
{

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'only' => ['index'],
                'rules' => [
                    [
                        'actions' => ['index'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Redirect to Tsheet Auth page
     *
     * @return string
     */
    public function actionReport1()
    {
        $data = new \app\models\Data();
        $provider = $data->search(\Yii::$app->request->get());

        return $this->render('report1', [
            'provider' => $provider,
            'filter' => $data,
        ]);

    }

    public function actionReport2()
    {
        $data = new \app\models\Data2();
        $provider = $data->search(\Yii::$app->request->get());

        return $this->render('report2', [
            'provider' => $provider,
            'filter' => $data,
        ]);

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
            $result = $this->tsheetService->exchangeAuthCode($code);
            $this->tsheetService->updateUserAuth($result);
            \Yii::$app->session->setFlash('success','Successful authentication');
        } catch (\Exception $e) {
            \Yii::info('tsheet auth error: ' . $e->getMessage() . ' | ' . $e->getTraceAsString());
        }

        return $this->redirect('/');
    }

    public function actionTimeEntries()
    {
        $date = \Yii::$app->request->post('date');
        try {
            $queryParams = [
                'start_date' => $date ?: date('Y-m-d'),
            ];
            $result = $this->tsheetService->requestGet('timesheets', $queryParams);
            $imported = $this->tsheetService->handleTimeSheet($result);
            \Yii::$app->session->setFlash('success', 'Success. Imported time sheets: ' . count($imported));
        } catch (\Exception $e) {
            $err = $e->getMessage() . ' | ' . $e->getLine() . ' | ' . $e->getFile();
            \Yii::$app->session->setFlash('error', $err);
            \Yii::info('tsheet error: ' . $e->getMessage() . ' | ' . $e->getTraceAsString());
        }
        $this->redirect('/');
    }
}
