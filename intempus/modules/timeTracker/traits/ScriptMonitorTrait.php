<?php

namespace app\modules\timeTracker\traits;

use GuzzleHttp\Client;

trait ScriptMonitorTrait
{

    protected function saveScriptStatus($scriptName, $status, $date)
    {
        return \Yii::$app->db->createCommand()->upsert('script_monitor', [
            'script_name' => $scriptName,
            'status' => $status,
            'execution_date' => $date,
            'created_at' => date('Y-m-d H:i:s'),
        ], [
            'status' => $status,
            'created_at' => date('Y-m-d H:i:s'),
        ])->execute();
    }

    protected function checkPreviousScriptSuccess($scriptName, $date)
    {
        $result = \Yii::$app->db->createCommand("
        SELECT status FROM script_monitor 
        WHERE script_name = :script AND execution_date = :date 
        ORDER BY created_at DESC LIMIT 1
    ")->bindValues([
            ':script' => $scriptName,
            ':date' => $date
        ])->queryScalar();

        return $result === 'success';
    }

}