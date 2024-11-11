<?php

namespace app\modules\timeTracker;

use Yii;
use yii\helpers\Html;
use yii\web\Session;
use app\modules\bonus\models\Score;
use app\modules\bonus\models\Transaction;

class Module extends \yii\base\Module implements \yii\base\BootstrapInterface
{
    public function bootstrap($app)
    {
        if ($app instanceof \yii\console\Application) {
            $this->controllerNamespace = 'app\modules\timeTracker\commands';
        }
    }

    public function init()
    {
        parent::init();

        $conf = require realpath(__DIR__ . '/../../config/timeTrackerConfig.php');
        $arr = [
            'params' => $conf['params'],
        ];
        \Yii::configure($this, $arr);
    }

}
