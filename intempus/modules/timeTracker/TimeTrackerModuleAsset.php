<?php

namespace app\modules\timeTracker;

use yii\web\AssetBundle;
use Yii;

/**
 */
class TimeTrackerModuleAsset extends AssetBundle
{
    public $sourcePath = '@app/modules/timeTracker/assets';
    /**
     * @var array
     */
    public $css = [
    ];
    /**
     * @var array
     */
    public $js = [

    ];

    public $depends = [
        'yii\web\YiiAsset',
        'yii\bootstrap5\BootstrapAsset',
        'yii\bootstrap5\BootstrapPluginAsset',
    ];

    public $publishOptions = [
        'forceCopy' => true,
    ];

    public function init()
    {
        parent::init();
    }
}
