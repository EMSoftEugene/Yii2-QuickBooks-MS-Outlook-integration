<?php

namespace app\controllers;

use Microsoft\Graph\GraphServiceClient;
use yii\web\Controller;
use yii\web\Response;
use Microsoft\Kiota\Authentication\Oauth\AuthorizationCodeContext;
use  Microsoft\Kiota\Authentication\Oauth\ProviderFactory;


class MicrosoftController extends Controller
{
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
    public function actionRedirect()
    {
        $code = \Yii::$app->request->post('code');
        $tokenRequestContext = new AuthorizationCodeContext(
            $_ENV['TENANT_ID'],
            $_ENV['CLIENT_ID'],
            $_ENV['CLIENT_SECRET'],
            $code,
            $_ENV['REDIRECT_URI'],
        );
        $scope = 'User.Read Group.Read.All';
        $graphClient = new GraphServiceClient($tokenRequestContext, $scope);

        $result = [];
        $events = $graphClient->groups()
            ->byGroupId('02a468f2-ad50-46a8-9b00-146814791243')
            ->events()
            ->get()->wait();

        foreach($events->getValue() as $event){
            $eventId = $event->getId();
            $locations[] = $event->getLocations();
            $result[] = [
                'eventId' => $eventId,
                'locations' => $locations,
            ];
        }

        \Yii::info('All evetns & locations: '.json_encode($result));
        return $this->redirect('site/index');
    }

}
