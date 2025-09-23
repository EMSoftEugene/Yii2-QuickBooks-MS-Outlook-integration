<?php

namespace app\modules\timeTracker\commands;

use app\modules\timeTracker\models\MicrosoftLocation;
use app\modules\timeTracker\services\TimeTrackerService;
use app\modules\timeTracker\services\TimeTrackerV2Service;
use app\modules\timeTracker\services\TsheetDataService;
use app\modules\timeTracker\traits\ScriptMonitorTrait;
use Yii;
use yii\console\ExitCode;
use yii\console\Controller;

/**
 * Class TimeTrackerController
 */
class TimeTrackerController extends Controller
{
    use ScriptMonitorTrait;

    private TimeTrackerService $timeTrackerService;
    private TimeTrackerV2Service $timeTrackerV2Service;

    public function __construct($id, $module, $config = [])
    {
        parent::__construct($id, $module, $config);
        $this->timeTrackerService = new TimeTrackerService();
        $this->timeTrackerV2Service = new TimeTrackerV2Service();
    }

    public function actionIndex()
    {
        $date = '2024-11-08';
        $date = new \DateTime();
        $date->modify('-1 days');
        $date = $date->format('Y-m-d');

        $addedRows = $this->timeTrackerService->create($date);

        echo "Successful added $addedRows new rows\n";
        return ExitCode::OK;
    }

    public function actionV2($date = null)
    {
        $date = $date ?: date('Y-m-d');

        try {
            $previousScripts = [
                'timeTracker/verizon/history',
                'timeTracker/microsoft/real-group'
            ];

            foreach ($previousScripts as $script) {
                if (!$this->checkPreviousScriptSuccess($script, $date)) {
                    echo "Previous script $script failed. Stopping execution.\n";
                    return ExitCode::UNSPECIFIED_ERROR;
                }
            }

            $addedRows = $this->timeTrackerV2Service->create($date);

            $this->saveScriptStatus('timeTracker/time-tracker/v2', 'success', $date);

            echo "Successful added $addedRows new rows\n";
            return ExitCode::OK;

        } catch (\Exception $e) {
            $this->saveScriptStatus('timeTracker/time-tracker/v2', 'failed', $date);
            Yii::error("V2 script failed: " . $e->getMessage());
            return ExitCode::UNSPECIFIED_ERROR;
        }
    }

}
