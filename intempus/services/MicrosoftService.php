<?php


namespace app\services;

use app\models\MicrosoftEvent;
use app\models\User;
use app\services\interfaces\MicrosoftInterface;
use Microsoft\Graph\BatchRequestBuilder;
use Microsoft\Graph\Core\Requests\BatchRequestContent;
use Microsoft\Graph\Generated\Groups\Item\Events\EventsRequestBuilderGetRequestConfiguration;
use Microsoft\Graph\GraphServiceClient;
use Microsoft\Kiota\Authentication\Oauth\AuthorizationCodeContext;

class MicrosoftService implements MicrosoftInterface
{
    public array $groupsEventScopes = [
        'User.Read',
        'Group.Read.All',
    ];

    public function getGraphClient(string $code): GraphServiceClient
    {
        $tokenRequestContext = new AuthorizationCodeContext(
            $_ENV['TENANT_ID'],
            $_ENV['CLIENT_ID'],
            $_ENV['CLIENT_SECRET'],
            $code,
            $_ENV['REDIRECT_URI'],
        );
        return new GraphServiceClient($tokenRequestContext, $this->groupsEventScopes);
    }

    public function getGroups(User $user): array
    {
        return ['02a468f2-ad50-46a8-9b00-146814791243', '502a6a6e-4ae4-47e2-bd54-f70a41f623dd', 'ed295d4e-2392-480f-b6ba-3f8479612706'];
        $graphClient = $this->getGraphClient($user->microsoft_auth);

        $groupIds = [];
        $groups = $graphClient->groups()->get()->wait();
        foreach ($groups->getValue() as $group) {
            $groupIds[] = $group->getId();
        }
        return $groupIds;
    }

    public function getEventsByGroupId(User $user, string $groupId): array
    {


        $graphClient = $this->getGraphClient($user->microsoft_auth);
        $result = [];
        $result[] = [
            'eventSubject' => 'asd',
            'eventStartTime' => '2020-12-12',
            'location' => 'asd',
        ];
        return $result;

        $result = [];
        $format = 'Y-m-d H:i:s';
        $date = new \DateTime();
        $startDate = $date->format($format);
        $date->add(new \DateInterval('P30D'));
        $endDate = $date->format($format);

        $requestConfiguration = new EventsRequestBuilderGetRequestConfiguration();
        $queryParameters = EventsRequestBuilderGetRequestConfiguration::createQueryParameters();
        $queryParameters->orderby = ["start/dateTime"];
        $queryParameters->select = ["subject", "locations", "start"];
        $queryParameters->top = 100;
        $queryParameters->filter = "start/dateTime gt '$startDate'  and start/dateTime lt '$endDate'";
        $requestConfiguration->queryParameters = $queryParameters;

        $events = $graphClient->groups()
            ->byGroupId($groupId)
            ->events()
            ->get($requestConfiguration)->wait();

        foreach ($events->getValue() as $event) {
            $eventId = $event->getId();
            $eventSubject = $event->getSubject();
            $eventTime = $event->getStart()->getDateTime();
            $tmpLocations = $event->getLocations();
            $location = '';
            foreach ($tmpLocations as $location) {
                $locationString = $location->getDisplayName();
                if ($locationString){
                    $location = $locationString;
                }
            }
            $result[] = [
                'eventSubject' => $eventSubject,
                'eventStartTime' => $eventTime,
                'location' => $location,
            ];
        }

        return $result;
    }

    public function saveEvents($events): void
    {
        foreach ($events as $event) {
            $eventLocation = $event['location'] ?? null;
            $existingEvent = MicrosoftEvent::findOne(['location' => $eventLocation]);
            if($existingEvent){
                continue;
            }
            $microsoftEvent= new MicrosoftEvent();
            $microsoftEvent->subject = $event['eventSubject'] ?? '';
            $microsoftEvent->eventStartTime = $event['eventStartTime'] ?? '';
            $microsoftEvent->location = $event['location'] ?? '';
            $microsoftEvent->save();
        }
    }

    public function getEventsBatch()
    {
        $tokenRequestContext = new AuthorizationCodeContext(
            $_ENV['TENANT_ID'],
            $_ENV['CLIENT_ID'],
            $_ENV['CLIENT_SECRET'],
            'code',
            $_ENV['REDIRECT_URI'],
        );
        $scopes = ['User.Read', 'Group.Read.All'];
        $graphClient = new GraphServiceClient($tokenRequestContext, $scopes);

        $format = 'Y-m-d H:i:s';
        $date = new \DateTime();
        $startDate = $date->format($format);
        $date->add(new \DateInterval('P30D'));
        $endDate = $date->format($format);

        $requestConfiguration = new EventsRequestBuilderGetRequestConfiguration();
        $queryParameters = EventsRequestBuilderGetRequestConfiguration::createQueryParameters();
        $queryParameters->orderby = ["start/dateTime"];
        $queryParameters->select = ["subject", "locations", "start"];
        $queryParameters->top = 100;
        $queryParameters->filter = "start/dateTime gt '$startDate'  and start/dateTime lt '$endDate'";
        $requestConfiguration->queryParameters = $queryParameters;

        $groups = $graphClient->groups()->get()->wait();
        $batchItems = [];
        foreach ($groups->getValue() as $group) {
            $groupId = $group->getId();
            $batchItems[] = $graphClient->groups()
                ->byGroupId($groupId)
                ->events()
                ->toGetRequestInformation($requestConfiguration);
        }

        $batchItems = array_slice($batchItems, 0, 20);
        $batchRequestContent = new BatchRequestContent($batchItems);

        $requestBuilder = new BatchRequestBuilder($graphClient->getRequestAdapter());
        $batchResponse = $requestBuilder->postAsync($batchRequestContent)->wait();

        $batchRequests = $batchRequestContent->getRequests();
        foreach ($batchRequests as $batchRequest) {
            $responseId = $batchRequest->getId();
            $response = $batchResponse->getResponse($responseId);
        }
    }

}