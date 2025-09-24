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
class MonitorController extends Controller
{
    use ScriptMonitorTrait;

    public function actionRestartFailed()
    {
        $date = date('Y-m-d');

        $scripts = [
            'timeTracker/verizon/history',
            'timeTracker/microsoft/real-group',
            'timeTracker/time-tracker/v2'
        ];

        foreach ($scripts as $script) {
            $status = $this->getScriptStatus($script, $date);

            if ($status == 'failed') {
                echo "Restarting failed script: $script\n";
                $this->restartScript($script, $date);
                break;
            } elseif ($status != 'success') {
                echo "Script $script not executed today. Starting...\n";
                $this->restartScript($script, $date);
            }
        }
    }

    private function restartScript($script, $date)
    {
        $command = "/usr/bin/php /var/www/outlook/intempus/yii $script $date";
        $output = shell_exec("$command 2>&1");
        echo "Output: $output\n";
    }

    private function getScriptStatus($scriptName, $date)
    {
        return Yii::$app->db->createCommand("
            SELECT status FROM script_monitor 
            WHERE script_name = :script AND execution_date = :date 
            ORDER BY created_at DESC LIMIT 1
        ")->bindValues([
            ':script' => $scriptName,
            ':date' => $date
        ])->queryScalar();
    }

}
