<?php

namespace app\modules\timeTracker\commands;

use app\modules\timeTracker\models\MicrosoftGroup;
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

    public function actionHistory($date = null)
    {
        Yii::info('actionHistory start');

        $startdatetimeutc = $date ? (new \DateTime($date))->modify('-1 days')->format('Y-m-d') . ' 00:00:00' :
            (new \DateTime())->modify('-3 days')->format('Y-m-d') . ' 00:00:00';
        $enddatetimeutc = $date ? (new \DateTime($date))->modify('+1 days')->format('Y-m-d') . ' 23:59:59'
            : (new \DateTime())->format('Y-m-d H:i:s');

        $groups = MicrosoftGroup::getAvailable();
        foreach ($groups as $group) {
            $vehiclenumber = $group['verizon_id'];

            $histories = $this->apiDataService->getVehiclesHistory($vehiclenumber, $startdatetimeutc, $enddatetimeutc);
            $addedNewHistories = 0;
            if ($histories) {
                $addedNewHistories = $this->apiDataService->saveNewHistories($histories);
            }
            Yii::info('actionHistory stopped. addedNewHistories=' . $addedNewHistories);
        }


        echo "Successful added Vehicles History : " . count($groups) . "\n";
        return ExitCode::OK;
    }

}
