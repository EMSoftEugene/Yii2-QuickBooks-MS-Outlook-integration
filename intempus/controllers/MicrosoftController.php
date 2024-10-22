<?php

namespace app\controllers;

use app\models\MicrosoftEvent;
use app\models\MicrosoftGroup;
use app\models\User;
use app\services\MicrosoftService;
use Microsoft\Graph\Generated\Groups\GroupsRequestBuilderGetRequestConfiguration;
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
use app\services\interfaces\MicrosoftInterface;

class MicrosoftController extends Controller
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
        $tokenRequestContext = new AuthorizationCodeContext(
            $_ENV['TENANT_ID'],
            $_ENV['CLIENT_ID'],
            $_ENV['CLIENT_SECRET'],
            'empty',
            $_ENV['REDIRECT_URI'],
        );

        $scope = 'offline_access User.Read Group.Read.All';
        $scope = 'User.Read Group.Read.All';
        $provider = ProviderFactory::create($tokenRequestContext);
        $authUrl = $provider->getAuthorizationUrl();
        $authUrl .= '&redirect_uri=' . urlencode($_ENV['REDIRECT_URI']);
        $authUrl .= '&response_mode=query';
        $authUrl .= '&client_id=' . $_ENV['CLIENT_ID'];
        $authUrl .= '&scope=' . urlencode($scope);
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
        if ($user) {
            $user->microsoft_auth = $code;
            $user->save();
            \Yii::$app->session->setFlash('Token updated');
        }

        $tokenRequestContext = new AuthorizationCodeContext(
            $_ENV['TENANT_ID'],
            $_ENV['CLIENT_ID'],
            $_ENV['CLIENT_SECRET'],
            $code,
            $_ENV['REDIRECT_URI'],
        );
        $graphClient = new GraphServiceClient($tokenRequestContext, $this->groupsEventScopes);

        $requestConfigurationGroup = new GroupsRequestBuilderGetRequestConfiguration();
        $queryParameters = GroupsRequestBuilderGetRequestConfiguration::createQueryParameters();
        $queryParameters->top = 500;
        $requestConfigurationGroup->queryParameters = $queryParameters;

        $groupIds = [];
        $groups = $graphClient->groups()->get($requestConfigurationGroup)->wait();
        foreach ($groups->getValue() as $group) {
            $microsoftGroup = new MicrosoftGroup();
            $microsoftGroup->name = $group->getDisplayName() ?? '-';
            $microsoftGroup->microsoft_id = $group->getId() ?? '-';
            $microsoftGroup->save();
            $groupIds[] = $group->getId();
        }

        $result = [];
        $format = 'Y-m-d H:i:s';
        $date = new \DateTime();
        $date->modify('first day of this month');
        $startDate = $date->format('Y-m-d') . ' 00:00:00';
        $date->add(new \DateInterval('P30D'));
        $endDate = $date->format($format);

        $requestConfiguration = new EventsRequestBuilderGetRequestConfiguration();
        $queryParameters = EventsRequestBuilderGetRequestConfiguration::createQueryParameters();
        $queryParameters->orderby = ["start/dateTime"];
        $queryParameters->select = ["subject", "locations", "start"];
        $queryParameters->top = 200;
//        $queryParameters->filter = "start/dateTime gt '$startDate' and start/dateTime lt '$endDate'";
        $queryParameters->filter = "start/dateTime gt '$startDate'";
        $requestConfiguration->queryParameters = $queryParameters;

        foreach ($groupIds as $groupId) {
            try {
                $events = $graphClient->groups()
                    ->byGroupId($groupId)
                    ->events()
                    ->get($requestConfiguration)->wait();
            } catch (\Exception $e) {
                continue;
            }

            foreach ($events->getValue() as $event) {
                $eventId = $event->getId();
                $eventSubject = $event->getSubject();
                $eventTime = $event->getStart()->getDateTime();
                $tmpLocations = $event->getLocations();

                $location = '';
                foreach ($tmpLocations as $location) {
                    $locationString = $location->getDisplayName();
                    if ($locationString) {
                        $location = $locationString;
                    }
                }
                if ($location){
                    $result[] = [
                        'eventSubject' => $eventSubject,
                        'eventStartTime' => $eventTime,
                        'location' => $location,
                    ];
                }
            }
        }

        foreach ($result as $event) {
            $eventLocation = $event['location'] ?? null;
            $existingEvent = MicrosoftEvent::findOne(['location' => $eventLocation]);
            if ($existingEvent) {
                continue;
            }
            $microsoftEvent = new MicrosoftEvent();
            $microsoftEvent->subject = $event['eventSubject'] ?? '';
            $microsoftEvent->eventStartTime = $event['eventStartTime'] ?? '';
            $microsoftEvent->location = $event['location'] ?? '';
            $microsoftEvent->save();
        }

        echo "!Ok";
        die;

        //////////////////////////


        $microsoftService = new \app\services\MicrosoftService();

        $groups = $microsoftService->getGroups($user);
        foreach ($groups as $groupId) {
            $result = $microsoftService->getEventsByGroupId($user, $groupId);
            $microsoftService->saveEvents($result);
        }

        echo "!Ok";
        die;

        return $this->redirect('/');
    }

    public function actionLocations()
    {
        $events = MicrosoftEvent::find()->select(['id', 'subject', 'eventStartTime', 'location'])->orderBy(['eventStartTime' => SORT_ASC])->asArray()->all();
        $groups = MicrosoftGroup::find()->select(['id', 'name'])->asArray()->all();

        echo "Groups: <pre>";
        print_r($groups);
        echo "</pre><br/><br/>";

        $result = [];
        foreach ($events as $event) {
            $date = new \DateTime($event['eventStartTime']);
            if (!isset($result[$date->format('Y-m-d')])) {
                $result[$date->format('Y-m-d')] = [];
            }
            $result[$date->format('Y-m-d')][] = $event;
        }

        foreach ($result as $key => $item)
        {
            echo "$key: <pre>";
            foreach ($item as $event) {
                print_r($event);
            }
            echo "</pre>";
        }
        die;
    }

}
