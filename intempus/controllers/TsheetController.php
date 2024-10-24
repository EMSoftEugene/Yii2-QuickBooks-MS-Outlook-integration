<?php

namespace app\controllers;

use app\models\MicrosoftGroup;
use app\models\User;
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
    public array $groupsEventScopes = [
        'User.Read',
        'Group.Read.All',
    ];

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
     * Displays homepage.
     *
     * @return Response
     */
    public function actionIndex()
    {
        $tsheetConfig = \Yii::$app->params['tsheet'];

        $authUrl = substr(Url::toRoute([$tsheetConfig['authorizationRequestUrl'],
                'response_type' => 'code',
                'client_id' => $tsheetConfig['client_id'],
                'redirect_uri' => $tsheetConfig['oauth_redirect_uri'],
                'state' => '12345',
            ]
        ), 1);

        return $this->redirect($authUrl);
    }


    /**
     * Displays homepage.
     *
     * @return Response
     */
    public function actionCallback()
    {
        $tsheetConfig = \Yii::$app->params['tsheet'];
        $code = \Yii::$app->request->get('code');
        $state = \Yii::$app->request->get('state');

        $client = new Client();
        $response = $client->request('GET', 'authorize', [
            'form_params' => [
                'grant_type' => 'authorization_code',
                'client_id' => $tsheetConfig['client_id'],
                'client_secret' => $tsheetConfig['client_id'],
                'code' => $code,
                'redirect_uri' => $tsheetConfig['oauth_redirect_uri'],
            ]
        ]);

        $result = $response->getBody();
        $result2 = $response->getBody()->getContents();
        $result3 = json_decode($response->getBody()->getContents(), true);

        \Yii::info('result= ' . print_r($result, true));
        \Yii::info('result2= ' . print_r($result2, true));
        \Yii::info('result3= ' . print_r($result3, true));

        echo "<pre>:";
        print_r($result);
        print_r($result2);
        print_r($result3);
        echo "!"; die;

        $user = User::findOne(['is_admin' => 1]);
        $user->save();

        return $this->redirect('/');
    }

    public function actionLocations()
    {


        die;
    }

}
