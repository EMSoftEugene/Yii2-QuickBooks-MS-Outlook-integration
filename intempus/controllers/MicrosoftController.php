<?php

namespace app\controllers;

use app\models\MicrosoftEvent;
use app\models\User;
use Microsoft\Graph\Generated\Groups\Item\Events\EventsRequestBuilderGetRequestConfiguration;
use Microsoft\Graph\GraphServiceClient;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\Response;
use Microsoft\Kiota\Authentication\Oauth\AuthorizationCodeContext;
use Microsoft\Kiota\Authentication\Oauth\ProviderFactory;
use Microsoft\Graph\Core\Requests\BatchRequestContent;
use Microsoft\Graph\BatchRequestBuilder;
use Microsoft\Graph\Core\Requests\BatchResponseItem;

class MicrosoftController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'only' => ['index', 'locations'],
                'rules' => [
                    [
                        'actions' => ['index', 'locations'],
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
        $tokenRequestContext = new AuthorizationCodeContext(
            $_ENV['TENANT_ID'],
            $_ENV['CLIENT_ID'],
            $_ENV['CLIENT_SECRET'],
            'empty',
            $_ENV['REDIRECT_URI'],
        );

        $scope = 'User.Read Group.Read.All';
        $provider = ProviderFactory::create($tokenRequestContext);
        $authUrl = $provider->getAuthorizationUrl();
        $authUrl.= '&redirect_uri='.urlencode($_ENV['REDIRECT_URI']);
        $authUrl.= '&response_mode=query';
        $authUrl.= '&client_id='.$_ENV['CLIENT_ID'];
        $authUrl.= '&scope='.$scope;
        return $this->redirect($authUrl);
    }


    /**
     * Displays homepage.
     *
     * @return Response
     */
    public function actionCallback()
    {
        $code = \Yii::$app->request->get('code');

        $user = User::findOne(['is_admin' => 1]);
        if($user){
            $user->microsoft_auth = $code;
            $user->save();
            \Yii::$app->session->setFlash('Token updated');
        }
        return $this->redirect('/');
    }

    public function actionLocations()
    {
        $events = MicrosoftEvent::find()->select(['id', 'subject','eventStartTime','location'])->asArray()->all();
        echo "Locations: <pre>";
        print_r($events);
        die;
    }

}
