<?php

namespace app\modules\timeTracker\commands;

use app\modules\timeTracker\models\MicrosoftGroup;
use app\modules\timeTracker\services\VerizonDataService;
use app\modules\timeTracker\traits\ScriptMonitorTrait;
use Yii;
use yii\console\ExitCode;
use yii\console\Controller;

/**
 * Class VerizonController
 */
class VerizonController extends Controller
{
    use ScriptMonitorTrait;

    private VerizonDataService $apiDataService;

    public function __construct($id, $module, $config = [])
    {
        parent::__construct($id, $module, $config);
        $this->apiDataService = new VerizonDataService();
    }

    public function actionHistory($date = null)
    {
        $date = $date ?: date('Y-m-d');

        try {
            Yii::info('actionHistory start');

            $startdatetimeutc = (new \DateTime($date))->modify('-1 days')->format('Y-m-d') . ' 00:00:00';
            $enddatetimeutc = (new \DateTime($date))->modify('+1 days')->format('Y-m-d') . ' 23:59:59';

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

            $this->saveScriptStatus('timeTracker/verizon/history', 'success', $date);

            echo "Successful added Vehicles History : " . count($groups) . "\n";
            return ExitCode::OK;

        } catch (\Exception $e) {
            $this->saveScriptStatus('timeTracker/verizon/history', 'failed', $date);
            Yii::error("History script failed: " . $e->getMessage());
            return ExitCode::UNSPECIFIED_ERROR;
        }
    }

}
