<?php

namespace app\controllers;

use app\models\MicrosoftGroup;
use app\models\User;
use app\services\interfaces\TsheetInterface;
use app\services\TsheetService;
use GuzzleHttp\Client;
use QuickBooksOnline\API\Core\OAuth\OAuth2\OAuth2AccessToken;
use QuickBooksOnline\API\DataService\DataService;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\helpers\Url;
use yii\web\Controller;
use yii\web\Response;

class TsheetController extends Controller
{
    private TsheetInterface $tsheetService;

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

    public function init()
    {
        parent::init();
        $this->tsheetService = new TsheetService();
    }

    /**
     * Redirect to Tsheet Auth page
     *
     * @return Response
     */
    public function actionIndex()
    {
        return $this->redirect($this->tsheetService->getAuthUrl());
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
                'date' => $date
            ];
            $result = $this->tsheetService->requestGet('time_off_request_entries', $queryParams);
            $imported = $this->tsheetService->handleTimeEntries($result);
            \Yii::$app->session->setFlash('success', 'Success. Imported time entries: ' . count($imported));
        } catch (\Exception $e) {
            \Yii::info('tsheet auth error: ' . $e->getMessage() . ' | ' . $e->getTraceAsString());
        }
        $this->redirect('/');
    }
}
