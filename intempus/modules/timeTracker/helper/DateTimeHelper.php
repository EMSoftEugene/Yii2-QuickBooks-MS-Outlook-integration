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

    public static function addMinutes($time, $add): string
    {
        list($hours, $minutes) = explode(":", $time);
        $total_minutes = $hours * 60 + $minutes;
        $new_total = $total_minutes + $add;
        $new_minutes = $new_total % 60;
        $new_hours = floor($new_total / 60);
        $new_hours = $new_hours % 24;
        $new_hours = str_pad($new_hours, 2, '0', STR_PAD_LEFT);
        $new_minutes = str_pad($new_minutes, 2, '0', STR_PAD_LEFT);
        $new_time = "$new_hours:$new_minutes";
        return $new_time;
    }

    public static function roundToHour($time): string
    {
        list($hours, $minutes) = explode(":", $time);
        $total_minutes = $hours * 60 + $minutes;
        $new_time = $time;
        if ($total_minutes < 60) {
            $new_time = "01:00";
        }
        return $new_time;
    }

    public static function complexRounding($time): string
    {
        list($hours, $minutes) = explode(":", $time);
        $newMinutes = 0;
        if ($minutes < 15) {
            $newMinutes = 0;
        } elseif ($minutes > 15 && $minutes < 45) {
            $newMinutes = 30;
        } elseif ($minutes >= 45) {
            $newMinutes = 60;
        }

        $total_minutes = $hours * 60 + $newMinutes;
        $new_minutes = $total_minutes % 60;
        $new_hours = floor($total_minutes / 60);
        $new_hours = $new_hours % 24;
        $new_hours = str_pad($new_hours, 2, '0', STR_PAD_LEFT);
        $new_minutes = str_pad($new_minutes, 2, '0', STR_PAD_LEFT);
        $new_time = "$new_hours:$new_minutes";
        return $new_time;
    }

    public static function diff($time1, $time2, $returnMinutes = false, $returnHours = false): string
    {
        list($hours1, $minutes1) = explode(":", $time1);
        $total_minutes1 = $hours1 * 60 + $minutes1;
        list($hours2, $minutes2) = explode(":", $time2);
        $total_minutes2 = $hours2 * 60 + $minutes2;

        $sign = '';
        if (($total_minutes1 - $total_minutes2) > 0) {
            $new_total_minutes = $total_minutes1 - $total_minutes2;
        } else {
            $sign = '-';
            $new_total_minutes = $total_minutes2 - $total_minutes1;
        }
        if ($returnMinutes) {
            return (int)($sign.$new_total_minutes);
        }
        if ($returnHours) {
            return (int)($sign.floor($new_total_minutes / 60));
        }
        $new_minutes = $new_total_minutes % 60;
        $new_hours = floor($new_total_minutes / 60);
        $new_hours = $new_hours % 24;
        $new_hours = str_pad($new_hours, 2, '0', STR_PAD_LEFT);
        $new_minutes = str_pad($new_minutes, 2, '0', STR_PAD_LEFT);
        $new_time = "$new_hours:$new_minutes";
        return $sign . $new_time;
    }

    public static function addition($time1, $time2): string
    {
        list($hours1, $minutes1) = explode(":", $time1);
        $total_minutes1 = $hours1 * 60 + $minutes1;
        list($hours2, $minutes2) = explode(":", $time2);
        $total_minutes2 = $hours2 * 60 + $minutes2;

        $new_total_minutes = $total_minutes1 + $total_minutes2;
        $new_minutes = $new_total_minutes % 60;
        $new_hours = floor($new_total_minutes / 60);
        $new_hours = $new_hours % 24;
        $new_hours = str_pad($new_hours, 2, '0', STR_PAD_LEFT);
        $new_minutes = str_pad($new_minutes, 2, '0', STR_PAD_LEFT);
        $new_time = "$new_hours:$new_minutes";
        return $new_time;
    }

    public static function formatHM($time): string
    {
        list($hours, $minutes) = explode(":", $time);
        $sign = mb_substr($hours,0,1);
        if ($sign === '-'){
            $hours = mb_substr($hours,1);
        } else {
            $sign = '';
        }
        $hours = $hours === '00' ?  '' : (int)$hours . 'h';
        $minutes = $minutes === '00' ?  '' : (int)$minutes . 'm';
        return $sign.$hours.$minutes;
    }

}