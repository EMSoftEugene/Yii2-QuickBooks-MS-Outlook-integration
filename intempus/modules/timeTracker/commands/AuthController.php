<?php

namespace app\modules\timeTracker\commands;

use app\modules\timeTracker\services\MicrosoftService;
use app\modules\timeTracker\services\TsheetService;
use Yii;
use yii\console\ExitCode;
use yii\console\Controller;

/**
 * Class TsheetController
 */
class AuthController extends Controller
{

    public function actionRefresh()
    {
//        $tsheetService = new TsheetService();
//        $tsheetService->refreshToken();

        $microsoftService = new MicrosoftService();
        $microsoftService->refreshToken();

        echo "Ok.";
        return ExitCode::OK;
    }

}
