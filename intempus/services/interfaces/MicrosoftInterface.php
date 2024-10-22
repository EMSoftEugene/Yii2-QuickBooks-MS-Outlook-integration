<?php


namespace app\services\interfaces;

use app\models\User;
use Microsoft\Graph\GraphServiceClient;

interface MicrosoftInterface
{
    public function getGraphClient(string $code): GraphServiceClient;

    public function getGroups(User $user): array;

    public function getEventsByGroupId(User $user, string $groupId): array;

}