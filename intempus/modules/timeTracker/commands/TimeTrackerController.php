<?php

namespace app\modules\timeTracker\commands;

use app\modules\timeTracker\models\MicrosoftLocation;
use app\modules\timeTracker\services\TimeTrackerService;
use app\modules\timeTracker\services\TimeTrackerV2Service;
use app\modules\timeTracker\services\TsheetDataService;
use Yii;
use yii\console\ExitCode;
use yii\console\Controller;

/**
 * Class TimeTrackerController
 */
class TimeTrackerController extends Controller
{
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

    public function actionV2()
    {
        $date = '2025-01-11';
        $date = new \DateTime($date);
        $date->modify('-1 days');
        $date = $date->format('Y-m-d');

        $addedRows = $this->timeTrackerV2Service->create($date);

        echo "Successful added $addedRows new rows\n";
        return ExitCode::OK;
    }

}
