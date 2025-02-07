<?php

namespace app\modules\timeTracker\commands;

use app\modules\timeTracker\services\MicrosoftService;
use app\modules\timeTracker\services\TsheetService;
use app\modules\timeTracker\services\VerizonService;
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

        $verizonService = new VerizonService();
        $verizonService->refreshToken();

        echo "Ok." . PHP_EOL;
        return ExitCode::OK;
    }

}
