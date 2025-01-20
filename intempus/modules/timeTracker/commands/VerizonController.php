<?php

namespace app\modules\timeTracker\commands;

use app\modules\timeTracker\services\VerizonDataService;
use Yii;
use yii\console\ExitCode;
use yii\console\Controller;

/**
 * Class VerizonController
 */
class VerizonController extends Controller
{
    private VerizonDataService $apiDataService;

    public function __construct($id, $module, $config = [])
    {
        parent::__construct($id, $module, $config);
        $this->apiDataService = new VerizonDataService();
    }

    public function actionHistory()
    {
        $startdatetimeutc = date('2025-01-11 00:00:00');
        $enddatetimeutc = date('2025-01-11 23:59:59');
        $vehiclenumber = 7;

        $histories = $this->apiDataService->getVehiclesHistory($vehiclenumber, $startdatetimeutc, $enddatetimeutc);
        $addedNewHistories = 0;
        if ($histories) {
            $addedNewHistories = $this->apiDataService->saveNewHistories($histories);
        }

        echo "Successful added $addedNewHistories new Vehicles History\n";
        return ExitCode::OK;
    }

}
