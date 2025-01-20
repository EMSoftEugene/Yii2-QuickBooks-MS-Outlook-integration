<?php

namespace app\modules\timeTracker\helper;

use Yii;
use yii\helpers\Html;
use yii\web\Session;

class DateTimeHelper
{
    public static function applyTimeZone($dateTime)
    {
        return (new \DateTime($dateTime))->setTimezone(new \DateTimeZone('America/Los_Angeles'))->format('Y-m-d H:i:s');
    }

}
