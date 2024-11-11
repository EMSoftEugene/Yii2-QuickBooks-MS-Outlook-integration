<?php

namespace app\modules\timeTracker\commands;

use app\modules\timeTracker\models\MicrosoftLocation;
use app\modules\timeTracker\services\TsheetDataService;
use Yii;
use yii\console\ExitCode;
use yii\console\Controller;

/**
 * Class TsheetController
 */
class TsheetController extends Controller
{
    private TsheetDataService $apiDataService;

    public function __construct($id, $module, $config = [])
    {
        parent::__construct($id, $module, $config);
        $this->apiDataService = new TsheetDataService();
    }

    public function actionUsers()
    {
        $users = $this->apiDataService->getUsers();

        if ($users) {
            $addedNewUsers = $this->apiDataService->saveNewUsers($users);
        }

        echo "Successful added $addedNewUsers new Users\n";
        return ExitCode::OK;
    }

    public function actionGeolocations()
    {
        $geolocations = $this->apiDataService->getGeolocations();

        if ($geolocations) {
            $addedNewGeolocations = $this->apiDataService->saveNewGeolocations($geolocations);
        }

        echo "Successful added $addedNewGeolocations new Geolocations\n";
        return ExitCode::OK;
    }

}
