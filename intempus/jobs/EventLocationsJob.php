<?php

namespace app\jobs;

use app\models\User;
use app\modules\timeTracker\services\MicrosoftServiceФксршму;
use yii\base\BaseObject;

class EventLocationsJob extends BaseObject implements \yii\queue\JobInterface
{
    public User $user;
    public string $groupId;

    public function execute($queue)
    {
        $microsoftService = new MicrosoftServiceФксршму();
        $events = $microsoftService->getEventsByGroupId($this->user, $this->groupId);
        $microsoftService->saveEvents($events);
    }
}