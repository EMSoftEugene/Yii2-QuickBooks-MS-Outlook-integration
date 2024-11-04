<?php
namespace app\modules\timeTracker;

use Yii;
use yii\helpers\Html;
use yii\web\Session;
use app\modules\bonus\models\Score;
use app\modules\bonus\models\Transaction;

class Module extends \yii\base\Module
{
    public string $userModel = 'app\models\User';

    public function init()
    {
		parent::init();

        $conf = require realpath(__DIR__ . '/../../config/timeTrackerConfig.php');
        $arr = [
            'params' => $conf['params'],
        ];
        \Yii::configure($this, $arr);


        if (isset($userModel)){
			$this->userModel = $userModel;
		}
    }

}
