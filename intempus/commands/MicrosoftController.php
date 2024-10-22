<?php

namespace app\commands;

use app\jobs\EventLocationsJob;
use app\models\User;
use app\services\interfaces\MicrosoftInterface;
use yii\console\Controller;
use yii\console\ExitCode;

class MicrosoftController extends Controller
{
    /**
     * @return int Exit code
     */
    public function actionIndex(MicrosoftInterface $microsoftService)
    {
        $user = User::findOne(['is_admin' => 1]);
        try {
            if (!$user) {
                throw new \Exception("User not found");
            }
            if (!$user->microsoft_auth) {
                throw new \Exception("Auth key not found");
            }

            $groups = $microsoftService->getGroups($user);
            foreach ($groups as $groupId) {
                \Yii::$app->queue->push(new EventLocationsJob([
                    'user' => $user,
                    'groupId' => $groupId,
                ]));
            }

        } catch (\Exception $exception) {
            echo $exception->getMessage();
            echo PHP_EOL;
            return ExitCode::DATAERR;
        }

        return ExitCode::OK;

    }
}
