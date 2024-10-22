<?php

namespace app\jobs;

use app\models\User;
use app\services\MicrosoftService;
use yii\base\BaseObject;

class EventLocationsJob extends BaseObject implements \yii\queue\JobInterface
{
    public User $user;
    public string $groupId;

    public function execute($queue)
    {
        $microsoftService = new MicrosoftService();
        $events = $microsoftService->getEventsByGroupId($this->user, $this->groupId);
        $microsoftService->saveEvents($events);
    }
}