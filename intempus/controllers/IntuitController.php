<?php

namespace app\controllers;

use app\models\MicrosoftGroup;
use app\models\User;
use QuickBooksOnline\API\Core\OAuth\OAuth2\OAuth2AccessToken;
use QuickBooksOnline\API\DataService\DataService;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\Response;

class IntuitController extends Controller
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
        $intuitConfig = \Yii::$app->params['intuit'];
        $dataService = DataService::Configure(array(
            'auth_mode' => 'oauth2',
            'ClientID' => $intuitConfig['client_id'],
            'ClientSecret' => $intuitConfig['client_secret'],
            'RedirectURI' => $intuitConfig['oauth_redirect_uri'],
            'scope' => $intuitConfig['oauth_scope'],
            'baseUrl' => "development"
        ));

        $OAuth2LoginHelper = $dataService->getOAuth2LoginHelper();
        $authUrl = $OAuth2LoginHelper->getAuthorizationCodeURL();

        return $this->redirect($authUrl);
    }


    /**
     * Displays homepage.
     *
     * @return Response
     */
    public function actionCallback()
    {
        $session = \Yii::$app->session;
        $authorizationCode = \Yii::$app->request->get('code');
        $realmId = \Yii::$app->request->get('realmId');

        // Create SDK instance
        $intuitConfig = \Yii::$app->params['intuit'];
        $dataService = DataService::Configure(array(
            'auth_mode' => 'oauth2',
            'ClientID' => $intuitConfig['client_id'],
            'ClientSecret' =>  $intuitConfig['client_secret'],
            'RedirectURI' => $intuitConfig['oauth_redirect_uri'],
            'scope' => $intuitConfig['oauth_scope'],
            'baseUrl' => "development"
        ));

        $OAuth2LoginHelper = $dataService->getOAuth2LoginHelper();

        /*
         * Update the OAuth2Token
         */
        $accessToken = $OAuth2LoginHelper->exchangeAuthorizationCodeForToken($authorizationCode, $realmId);
        $dataService->updateOAuth2Token($accessToken);

        $user = User::findOne(['is_admin' => 1]);
        $user->intuit_access_token = $accessToken->getAccessToken();
        $user->refresh_token = $accessToken->getRefreshToken();
        $user->x_refresh_token_expires_in = $accessToken->getRefreshTokenExpiresAt();
        $user->expires_in = $accessToken->getAccessTokenExpiresAt();
        $user->save();

        $session->set('sessionAccessToken', $accessToken);
        return $this->redirect('/');
    }

    public function actionLocations()
    {


        die;
    }

}
