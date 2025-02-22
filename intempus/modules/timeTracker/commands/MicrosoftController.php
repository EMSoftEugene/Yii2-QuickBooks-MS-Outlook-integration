<?php

namespace app\modules\timeTracker\commands;

use app\modules\timeTracker\models\MicrosoftLocation;
use app\modules\timeTracker\services\interfaces\ApiInterface;
use app\modules\timeTracker\services\MicrosoftDataService;
use app\modules\timeTracker\services\MicrosoftService;
use Yii;
use yii\console\ExitCode;
use yii\console\Controller;
use yii\helpers\ArrayHelper;

/**
 * Class MicrosoftController
 */
class MicrosoftController extends Controller
{
    private MicrosoftDataService $apiDataService;

    public function __construct($id, $module, $config = [])
    {
        parent::__construct($id, $module, $config);
        $this->apiDataService = new MicrosoftDataService();
    }

    public function actionGroups()
    {
        $groups = $this->apiDataService->getGroups();

        if ($groups) {
            $addedNewGroups = $this->apiDataService->saveNewGroups($groups);
        }

        echo "Successful added $addedNewGroups new Groups\n";
        return ExitCode::OK;
    }

    public function actionLocations()
    {
        $locations = $this->apiDataService->getLocations();

        if ($locations) {
            $addedNewLocations = $this->apiDataService->saveNewLocations($locations);
        }

        echo "Successful added $addedNewLocations new Locations\n";
        return ExitCode::OK;
    }

    public function actionGeocode()
    {
        $locations = MicrosoftLocation::find()->where(['lat'=> null])->where(['lon'=> null])->all();

        if ($locations) {
            $addedNewGeocode = $this->apiDataService->geocode($locations);
        }

        echo "Successful added $addedNewGeocode new Geocode\n";
        return ExitCode::OK;
    }

    public function actionRealGroup($date = null)
    {
        $dateTimeStart = $date ? (new \DateTime($date))->format('Y-m-d') : (new \DateTime())->modify('-2 days')->format('Y-m-d');
        $dateTimeEnd = $date ? (new \DateTime($date))->modify('+1 days')->format('Y-m-d') : (new \DateTime())->modify('-1 days')->format('Y-m-d');

        $groups = $this->apiDataService->groupsByNameAndDate($dateTimeStart, $dateTimeEnd);

        echo "Successful found " . count($groups) . " Location\n";
        return ExitCode::OK;
    }



}
