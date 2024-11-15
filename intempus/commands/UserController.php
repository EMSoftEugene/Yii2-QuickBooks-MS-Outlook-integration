<?php

namespace app\commands;

use app\models\User;
use yii\console\Controller;
use yii\console\ExitCode;

class UserController extends Controller
{
    /**
     * This command echoes what you have entered as the message.
     * @return int Exit code
     */
    public function actionIndex()
    {
        $user = new User();
        $user->username = 'admin';
        $user->email = 'admin@admin.admin';
        $user->setPassword('adminqwe123');
        $user->generateAuthKey();
        $user->save();

        return ExitCode::OK;
    }
}
