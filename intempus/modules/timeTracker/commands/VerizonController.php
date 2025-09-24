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
        if ($date === null) {
            $date = (new \DateTime())->modify('-1 days')->format('Y-m-d');
        }

        $date = (new \DateTime($date))->format('Y-m-d');
        try {
            Yii::info('actionHistory start');

            $startdatetimeutc = (new \DateTime($date . ' 00:00:00', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s');
            $enddatetimeutc = (new \DateTime($date . ' 23:59:59', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s');

            echo "Processing date: $date\n";
            echo "UTC Date range: $startdatetimeutc to $enddatetimeutc\n";

            $groups = MicrosoftGroup::getAvailable();
            foreach ($groups as $group) {
                $vehiclenumber = $group['verizon_id'];
                echo "Fetching history for vehicle: $vehiclenumber\n";

                $histories = $this->apiDataService->getVehiclesHistory($vehiclenumber, $startdatetimeutc, $enddatetimeutc);
                $addedNewHistories = 0;
                if ($histories) {
                    $addedNewHistories = $this->apiDataService->saveNewHistories($histories);
                }
                echo "Saved $addedNewHistories new records for $vehiclenumber\n";
            }

            $this->saveScriptStatus('timeTracker/verizon/history', 'success', $date);
            echo "Successful added Vehicles History : " . count($groups) . "\n";
            return ExitCode::OK;

        } catch (\Exception $e) {
            $this->saveScriptStatus('timeTracker/verizon/history', 'failed', $date);
            echo "Error: " . $e->getMessage() . "\n";
            Yii::error("History script failed: " . $e->getMessage());
            return ExitCode::UNSPECIFIED_ERROR;
        }
    }

}
